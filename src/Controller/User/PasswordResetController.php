<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Helper\StringHelper;
use DateTime;
use Exception;
use SensioLabs\Security\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class PasswordResettingController
 * @package App\Controller\User
 * @Route("password-reset")
 */
class PasswordResetController extends DefaultController
{
    /**
     * Renders and handles password resetting request form.
     *
     * @param Request $request
     * @Route("/request", name="password_reset_request", methods={"GET", "POST"})
     * @return Response
     * @throws Exception
     */
    public function requestAction(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('password_reset_request', $request->get('csrfToken')) === false) {
                throw new HttpException(400);
            }

            $em = $this->getDoctrine()->getManager();
            $usernameOrEmail = StringHelper::truncateToMySQLVarcharMaxLength(
                $request->request->get('usernameOrEmail')
            );

            if (preg_match('/^.+\@\S+\.\S+$/', $usernameOrEmail)) {
                $user = $em->getRepository('App:User')->findOneBy(['email' => $usernameOrEmail]);
            } else {
                $user = $em->getRepository('App:User')->findOneBy(['username' => $usernameOrEmail]);
            }

            $this->addFlash(
                'password-reset-request-success',
                $this->get('translator')->trans('flash.user.password_reset_email_sent')
            );

            if ($user === null) {
                return $this->render(':User:password-reset-request.html.twig');
            }

            $passwordResetRequestRetryDelay = $this->getParameter('password_reset_request_send_email_again_delay');

            // IF retry delay is not expired, only show success message without sending email and writing in database.
            if ($user->getPasswordResetRequestedAt() !== null
                && $user->isPasswordResetRequestRetryDelayExpired($passwordResetRequestRetryDelay) === false) {
                return $this->render(':User:password-reset-request.html.twig');
            }

            // Generates password reset token and retries if token already exists.
            $loop = true;
            while ($loop) {
                $token = $user->generateSecureToken();

                $duplicate = $em->getRepository('App:User')->findOneBy(['passwordResetToken' => $token]);
                if (is_null($duplicate)) {
                    $loop = false;
                    $user->setPasswordResetToken($token);
                }
            }

            $user->setPasswordResetRequestedAt(new DateTime());

            $passwordResetTokenLifetimeInMinutes = ceil($this->getParameter('password_reset_token_lifetime') / 60);
            $this->get('mailer.service')->passwordResetRequest(
                $user, $passwordResetTokenLifetimeInMinutes,
                $request->getLocale()
            );

            $em->flush();
        }

        return $this->render(':User:password-reset-request.html.twig');
    }

    /**
     * Renders and handles password reset form.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @Route("/reset", name="password_reset", methods={"GET", "POST"})
     * @return Response
     */
    public function resetAction(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $passwordResetToken = $request->get('token');

        if (empty($passwordResetToken)) {
            return $this->redirectToRoute('password_reset_request');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App:User')->findOneBy([
            'passwordResetToken' => StringHelper::truncateToMySQLVarcharMaxLength($passwordResetToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'password-reset-error',
                $this->get('translator')->trans('flash.user.password_reset_token_expired')
            );

            return $this->redirectToRoute('password_reset_request');
        }

        $passwordResetTokenLifetime = $this->getParameter('password_reset_token_lifetime');

        if ($user->isPasswordResetTokenExpired($passwordResetTokenLifetime)) {
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            $em->flush();

            $this->addFlash(
                'password-reset-error',
                $this->get('translator')->trans('flash.user.password_reset_token_expired')
            );

            return $this->redirectToRoute('password_reset_request');
        }

        $form = $this->createForm('App\Form\User\PasswordResetType', $user);

        $form->handleRequest($request);

        /*
         * User just submitted a password reset form, so we consider his email address has successfully been verified,
         * even if user never actually activated his account through the dedicated feature.
         */
        if ($form->isSubmitted() && $user->isActivated() === false) {
            $user->setActivated(true);
            $em->flush();
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->setPassword($hashedPassword);
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            $em->flush();

            $this->addFlash(
                'password-reset-success',
                $this->get('translator')->trans('flash.user.password_reset_success')
            );

            return $this->redirectToRoute('login');
        }

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordResetToken()
        ];

        return $this->render(':User:password-reset-reset.html.twig', [
            'form' => $form->createView(),
            'passwordBlacklist' => json_encode($passwordBlacklist)
        ]);
    }
}
