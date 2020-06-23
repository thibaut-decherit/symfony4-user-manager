<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Entity\User;
use App\Form\User\EmailChangeType;
use App\Helper\StringHelper;
use App\Model\AbstractUser;
use App\Service\MailerService;
use App\Service\UniqueRandomDataGeneratorService;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class EmailChangeController
 * @package App\Controller\User
 *
 */
class EmailChangeController extends DefaultController
{
    /**
     * Renders the email address change form.
     *
     * @return Response
     */
    public function changeForm(): Response
    {
        /**
         * @var AbstractUser $user
         */
        $user = $this->getUser();

        $form = $this->createForm(EmailChangeType::class, $user);

        return $this->render('form/user/_email_change.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Handles the email address change form submitted with ajax.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param MailerService $mailer
     * @param UniqueRandomDataGeneratorService $uniqueRandomDataGenerator
     * @Route("/account/email-change/request-ajax", name="email_change_request_ajax", methods="POST")
     * @return JsonResponse
     * @throws Exception
     */
    public function changeRequest(
        Request $request,
        TranslatorInterface $translator,
        MailerService $mailer,
        UniqueRandomDataGeneratorService $uniqueRandomDataGenerator
    ): JsonResponse
    {
        /**
         * @var AbstractUser $user
         */
        $user = $this->getUser();

        $form = $this->createForm(EmailChangeType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getEmailChangePending() === $user->getEmail()) {
                $this->addFlash(
                    'email-change-request-error',
                    $translator->trans('flash.user.already_current_email_address')
                );

                $template = $this->renderView('form/user/_email_change.html.twig', [
                    'form' => $form->createView()
                ]);

                return new JsonResponse([
                    'template' => json_encode($template)
                ], 422);
            }

            $emailChangeRequestRetryDelay = $this->getParameter('app.email_change_request_send_email_again_delay');

            // IF retry delay is not expired, displays error message.
            if ($user->getEmailChangeRequestedAt() !== null
                && $user->isEmailChangeRequestRetryDelayExpired($emailChangeRequestRetryDelay) === false) {
                // Displays a flash message informing user that he has to wait $limit minutes between each attempt
                $limit = ceil($emailChangeRequestRetryDelay / 60);
                $errorMessage = '';

                if ($limit < 2) {
                    $errorMessage = $translator->trans(
                        'flash.user.verification_link_retry_delay_not_expired_singular'
                    );
                } else {
                    $errorMessage = $translator->trans(
                        'flash.user.verification_link_retry_delay_not_expired_plural',
                        [
                            '%delay%' => $limit
                        ]
                    );
                }

                $this->addFlash(
                    'email-change-request-error',
                    $errorMessage
                );

                $form = $this->createForm(EmailChangeType::class, $user);
                $template = $this->renderView('form/user/_email_change.html.twig', [
                    'form' => $form->createView()
                ]);

                return new JsonResponse([
                    'template' => json_encode($template)
                ], 200);
            }

            $em = $this->getDoctrine()->getManager();

            $user->setEmailChangeToken(
                $uniqueRandomDataGenerator->uniqueRandomString(
                    User::class,
                    'emailChangeToken'
                )
            );

            $user->setEmailChangeRequestedAt(new DateTime());

            // IF email address is not already registered to another account, sends verification email.
            $duplicate = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmailChangePending()]);
            if (is_null($duplicate)) {
                $emailChangeTokenLifetimeInMinutes = ceil(
                    $this->getParameter('app.email_change_token_lifetime') / 60
                );
                $mailer->emailChange($user, $emailChangeTokenLifetimeInMinutes, $request->getLocale());
            }

            $em->flush();

            $successMessage = $this->render('flash_alert/raw_messages/user/_email_change_request_success.html.twig', [
                'user' => $user
            ]);
            $this->addFlash(
                'email-change-request-success-raw',
                $successMessage->getContent()
            );

            $form = $this->createForm(EmailChangeType::class, $user);
            $template = $this->renderView('form/user/_email_change.html.twig', [
                'form' => $form->createView()
            ]);

            return new JsonResponse([
                'template' => json_encode($template)
            ], 200);
        }

        // Renders and json encode the updated form (with errors).
        $template = $this->renderView('form/user/_email_change.html.twig', [
            'form' => $form->createView(),
        ]);

        // Returns the html form and 422 Unprocessable Entity status to js.
        return new JsonResponse([
            'template' => json_encode($template)
        ], 422);
    }

    /**
     * Renders email change confirmation view where user can click a button to confirm or cancel the change.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @Route("/email-change/confirm", name="email_change_confirm", methods="GET")
     * @return RedirectResponse
     */
    public function confirm(Request $request, TranslatorInterface $translator): Response
    {
        $validation = $this->validateConfirmation($request->get('token'), $translator);

        if ($validation['isValid'] === false) {
            return $this->redirectToRoute('home');
        }

        return $this->render('user/email_change_confirm.html.twig', [
            'user' => $validation['user']
        ]);
    }

    /**
     * Cancels email change of account matching token.
     *
     * @param Request $request
     * @Route("/email-change/cancel", name="email_change_cancel", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function cancel(Request $request): RedirectResponse
    {
        if ($this->isCsrfTokenValid('email_change_cancel', $request->get('_csrf_token')) === false) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $emailChangeToken = $request->get('email_change_token');

        if (empty($emailChangeToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /**
         * @var User $user
         */
        $user = $em->getRepository(User::class)->findOneBy([
            'emailChangeToken' => StringHelper::truncateToMySQLVarcharMaxLength($emailChangeToken)
        ]);

        if ($user !== null) {
            $user->setEmailChangePending(null);
            $user->setEmailChangeRequestedAt(null);
            $user->setEmailChangeToken(null);

            $em->flush();
        }

        return $this->redirectToRoute('home');
    }

    /**
     * Changes email of account matching token if token is not expired.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @Route("/email-change/change", name="email_change", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function change(Request $request, TranslatorInterface $translator): RedirectResponse
    {
        if ($this->isCsrfTokenValid('email_change', $request->get('_csrf_token')) === false) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $validation = $this->validateConfirmation($request->get('email_change_token'), $translator);

        if ($validation['isValid'] === false) {
            return $this->redirectToRoute('home');
        }

        $user = $validation['user'];

        $em = $this->getDoctrine()->getManager();

        $duplicate = $em->getRepository(User::class)->findOneBy([
            'email' => $user->getEmailChangePending()
        ]);

        if ($duplicate === null) {
            $user->setEmail($user->getEmailChangePending());
        }

        $successMessage = $this->render('flash_alert/raw_messages/user/_email_change_success.html.twig', [
            'user' => $user
        ]);

        $user->setEmailChangeToken(null);
        $user->setEmailChangeRequestedAt(null);
        $user->setEmailChangePending(null);
        $em->flush();

        $this->addFlash(
            'email-change-success-raw',
            $successMessage->getContent()
        );

        return $this->redirectToRoute('home');
    }

    /**
     * Handles the validation logic shared between $this->confirm() and $this->change().
     * Returns an array containing a bool 'isValid' and a User|null 'user'.
     *
     * @param string $emailChangeToken
     * @param TranslatorInterface $translator
     * @return array
     */
    private function validateConfirmation(string $emailChangeToken, TranslatorInterface $translator): array
    {
        $validation = [
            'isValid' => false,
            'user' => null
        ];

        if (empty($emailChangeToken)) {
            return $validation;
        }

        $em = $this->getDoctrine()->getManager();

        /**
         * @var User $user
         */
        $user = $em->getRepository(User::class)->findOneBy([
            'emailChangeToken' => StringHelper::truncateToMySQLVarcharMaxLength($emailChangeToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'email-change-error',
                $translator->trans('flash.user.email_change_token_expired')
            );

            return $validation;
        }

        $validation['user'] = $user;

        $emailChangeTokenLifetime = $this->getParameter('app.email_change_token_lifetime');

        if ($user->isEmailChangeTokenExpired($emailChangeTokenLifetime)) {
            $user->setEmailChangePending(null);
            $user->setEmailChangeRequestedAt(null);
            $user->setEmailChangeToken(null);

            $em->flush();

            $this->addFlash(
                'email-change-error',
                $translator->trans('flash.user.email_change_token_expired')
            );

            return $validation;
        }

        $validation['isValid'] = true;

        return $validation;
    }
}
