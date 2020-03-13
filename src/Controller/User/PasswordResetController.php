<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Entity\User;
use App\Form\User\PasswordResetType;
use App\Helper\StringHelper;
use App\Service\MailerService;
use App\Service\UniqueRandomDataGeneratorService;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class PasswordResettingController
 * @package App\Controller\User
 * @Route("password-reset")
 */
class PasswordResetController extends DefaultController
{
    /**
     * Renders the password resetting request form.
     *
     * @Route("/request", name="password_reset_request", methods="GET")
     * @return Response
     */
    public function requestForm(): Response
    {
        return $this->render('user/password_reset_request.html.twig');
    }

    /**
     * Handles the password resetting request form submitted with ajax.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param MailerService $mailer
     * @param UniqueRandomDataGeneratorService $uniqueRandomDataGenerator
     * @Route("/request-ajax", name="password_reset_request_ajax", methods="POST")
     * @return JsonResponse
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function request(
        Request $request,
        TranslatorInterface $translator,
        MailerService $mailer,
        UniqueRandomDataGeneratorService $uniqueRandomDataGenerator
    ): JsonResponse
    {
        if ($this->isCsrfTokenValid('password_reset_request', $request->get('csrfToken')) === false) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $em = $this->getDoctrine()->getManager();
        $businessUsernameOrEmail = StringHelper::truncateToMySQLVarcharMaxLength(
            $request->request->get('businessUsernameOrEmail')
        );

        if (preg_match('/^.+@\S+\.\S+$/', $businessUsernameOrEmail)) {
            /**
             * @var User $user
             */
            $user = $em->getRepository(User::class)->findOneBy(['email' => $businessUsernameOrEmail]);
        } else {
            /**
             * @var User $user
             */
            $user = $em->getRepository(User::class)->findOneBy(['businessUsername' => $businessUsernameOrEmail]);
        }

        $this->addFlash(
            'password-reset-request-success',
            $translator->trans('flash.user.password_reset_email_sent')
        );

        // Renders and json encode the updated form (with flash message)
        $template = $this->render('form/user/_password_reset_request.html.twig');
        $jsonTemplate = json_encode($template->getContent());

        if ($user === null) {
            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        $passwordResetRequestRetryDelay = $this->getParameter('app.password_reset_request_send_email_again_delay');

        // IF retry delay is not expired, only show success message without sending email and writing in database.
        if ($user->getPasswordResetRequestedAt() !== null
            && $user->isPasswordResetRequestRetryDelayExpired($passwordResetRequestRetryDelay) === false) {
            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        $user->setPasswordResetToken(
            $uniqueRandomDataGenerator->uniqueRandomString(
                User::class,
                'passwordResetToken'
            )
        );

        $user->setPasswordResetRequestedAt(new DateTime());

        $passwordResetTokenLifetimeInMinutes = ceil(
            $this->getParameter('app.password_reset_token_lifetime') / 60
        );
        $mailer->passwordResetRequest($user, $passwordResetTokenLifetimeInMinutes, $request->getLocale());

        $em->flush();

        return new JsonResponse([
            'template' => $jsonTemplate
        ], 200);
    }

    /**
     * Renders the password reset form.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @Route("/reset", name="password_reset", methods="GET")
     * @return Response
     */
    public function resetForm(
        Request $request,
        TranslatorInterface $translator
    ): Response
    {
        $passwordResetToken = $request->get('token');

        if (empty($passwordResetToken)) {
            return $this->redirectToRoute('password_reset_request');
        }

        $em = $this->getDoctrine()->getManager();

        /**
         * @var User $user
         */
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

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $this->getParameter('app.website_name'),
            $user->getBusinessUsername(),
            $user->getEmail(),
            $user->getPasswordResetToken()
        ];

        return $this->render('user/password_reset_reset.html.twig', [
            'form' => $form->createView(),
            'password_blacklist' => json_encode($passwordBlacklist)
        ]);
    }

    /**
     * Handles the password reset form submitted with ajax.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TranslatorInterface $translator
     * @Route("/reset-ajax", name="password_reset_ajax", methods="POST")
     * @return JsonResponse
     */
    public function reset(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator
    ): JsonResponse
    {
        if (empty($request->get('App_user')['passwordResetToken'])) {
            throw new BadRequestHttpException('Invalid password reset token.');
        }

        $passwordResetToken = $request->get('App_user')['passwordResetToken'];

        $em = $this->getDoctrine()->getManager();

        /**
         * @var User $user
         */
        $user = $em->getRepository(User::class)->findOneBy([
            'passwordResetToken' => StringHelper::truncateToMySQLVarcharMaxLength($passwordResetToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'password-reset-error',
                $translator->trans('flash.user.password_reset_token_expired')
            );

            return new JsonResponse([
                'isTokenExpired' => true,
                'url' => $this->generateUrl(
                    'password_reset_request',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ], 400);
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

            return new JsonResponse([
                'isTokenExpired' => true,
                'url' => $this->generateUrl(
                    'password_reset_request',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ], 400);
        }

        $form = $this->createForm(PasswordResetType::class, $user);

        $form->handleRequest($request);

        /*
         * User just submitted a password reset form, so we consider his email address has successfully been verified,
         * even if user never actually activated his account through the dedicated feature.
         */
        if ($user->isActivated() === false) {
            $user->setActivated(true);
            $user->setAccountActivationToken(null);
            $em->flush();
        }

        if ($form->isValid()) {
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->setPassword($hashedPassword);
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            $em->flush();

            $this->addFlash(
                'password-reset-success',
                $translator->trans('flash.user.password_reset_success')
            );

            return new JsonResponse([
                'url' => $this->generateUrl(
                    'login',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ], 200);
        }

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $this->getParameter('app.website_name'),
            $user->getBusinessUsername(),
            $user->getEmail(),
            $user->getPasswordResetToken()
        ];

        // Renders and json encode the updated form (with flash message)
        $template = $this->render('form/user/_password_reset.html.twig', [
            'form' => $form->createView(),
            'password_blacklist' => json_encode($passwordBlacklist)
        ]);
        $jsonTemplate = json_encode($template->getContent());

        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }
}
