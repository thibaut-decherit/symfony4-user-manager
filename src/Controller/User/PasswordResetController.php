<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Entity\User;
use App\Form\User\PasswordResetType;
use App\Helper\StringHelper;
use App\Service\MailerService;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @param TranslatorInterface $translator
     * @param MailerService $mailer
     * @Route("/request", name="password_reset_request", methods={"GET", "POST"})
     * @return Response
     * @throws Exception
     */
    public function request(
        Request $request,
        TranslatorInterface $translator,
        MailerService $mailer
    ): Response
    {
        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('password_reset_request', $request->get('csrfToken')) === false) {
                throw new AccessDeniedException('Invalid CSRF token.');
            }

            $em = $this->getDoctrine()->getManager();
            $usernameOrEmail = StringHelper::truncateToMySQLVarcharMaxLength(
                $request->request->get('usernameOrEmail')
            );

            if (preg_match('/^.+@\S+\.\S+$/', $usernameOrEmail)) {
                $user = $em->getRepository(User::class)->findOneBy(['email' => $usernameOrEmail]);
            } else {
                $user = $em->getRepository(User::class)->findOneBy(['username' => $usernameOrEmail]);
            }

            $this->addFlash(
                'password-reset-request-success',
                $translator->trans('flash.user.password_reset_email_sent')
            );

            if ($user === null) {
                return $this->render('user/password_reset_request.html.twig');
            }

            $passwordResetRequestRetryDelay = $this->getParameter('app.password_reset_request_send_email_again_delay');

            // IF retry delay is not expired, only show success message without sending email and writing in database.
            if ($user->getPasswordResetRequestedAt() !== null
                && $user->isPasswordResetRequestRetryDelayExpired($passwordResetRequestRetryDelay) === false) {
                return $this->render('user/password_reset_request.html.twig');
            }

            // Generates password reset token and retries if token already exists.
            $loop = true;
            while ($loop) {
                $token = StringHelper::generateRandomString();

                $duplicate = $em->getRepository(User::class)->findOneBy(['passwordResetToken' => $token]);
                if (is_null($duplicate)) {
                    $loop = false;
                    $user->setPasswordResetToken($token);
                }
            }

            $user->setPasswordResetRequestedAt(new DateTime());

            $passwordResetTokenLifetimeInMinutes = ceil($this->getParameter('app.password_reset_token_lifetime') / 60);
            $mailer->passwordResetRequest($user, $passwordResetTokenLifetimeInMinutes, $request->getLocale());

            $em->flush();
        }

        return $this->render('user/password_reset_request.html.twig');
    }

    /**
     * Renders and handles password reset form.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TranslatorInterface $translator
     * @Route("/reset", name="password_reset", methods={"GET", "POST"})
     * @return Response
     */
    public function reset(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator
    ): Response
    {
        $passwordResetToken = $request->get('token');

        if (empty($passwordResetToken)) {
            return $this->redirectToRoute('password_reset_request');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository(User::class)->findOneBy([
            'passwordResetToken' => StringHelper::truncateToMySQLVarcharMaxLength($passwordResetToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'password-reset-error',
                $translator->trans('flash.user.password_reset_token_expired')
            );

            return $this->redirectToRoute('password_reset_request');
        }

        $passwordResetTokenLifetime = $this->getParameter('app.password_reset_token_lifetime');

        if ($user->isPasswordResetTokenExpired($passwordResetTokenLifetime)) {
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            $em->flush();

            $this->addFlash(
                'password-reset-error',
                $translator->trans('flash.user.password_reset_token_expired')
            );

            return $this->redirectToRoute('password_reset_request');
        }

        $form = $this->createForm(PasswordResetType::class, $user);

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
                $translator->trans('flash.user.password_reset_success')
            );

            return $this->redirectToRoute('login');
        }

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $this->getParameter('app.website_name'),
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordResetToken()
        ];

        return $this->render('user/password_reset_reset.html.twig', [
            'form' => $form->createView(),
            'password_blacklist' => json_encode($passwordBlacklist)
        ]);
    }
}
