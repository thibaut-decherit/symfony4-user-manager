<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Helper\StringHelper;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class EmailChangeController
 * @package AppBundle\Controller\User
 *
 */
class EmailChangeController extends DefaultController
{
    /**
     * Renders the email address change form.
     *
     * @return Response
     */
    public function changeFormAction(): Response
    {
        $user = $this->getUser();

        $form = $this->createForm('AppBundle\Form\User\EmailChangeType', $user);

        return $this->render(':Form/User:email-change.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Handles the email address change form submitted with ajax.
     *
     * @param Request $request
     * @Route("account/email-change/request-ajax", name="email_change_request_ajax", methods="POST")
     * @return JsonResponse
     * @throws Exception
     */
    public function changeRequestAction(Request $request): JsonResponse
    {
        $user = $this->getUser();

        $form = $this->createForm('AppBundle\Form\User\EmailChangeType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getEmailChangePending() === $user->getEmail()) {
                /*
                 * $user must be refreshed or invalid POST data will conflict with logged-in user and crash the session,
                 * this line is not needed when editing with ajax any other entity than User
                 */
                $this->getDoctrine()->getManager()->refresh($user);

                $this->addFlash(
                    'email-change-request-error',
                    $this->get('translator')->trans('flash.user.already_current_email_address')
                );

                $template = $this->render(':Form/User:email-change.html.twig', [
                    'form' => $form->createView()
                ]);
                $jsonTemplate = json_encode($template->getContent());

                return new JsonResponse([
                    'template' => $jsonTemplate
                ], 400);
            }

            $emailChangeRequestRetryDelay = $this->getParameter('email_change_request_send_email_again_delay');

            // IF retry delay is not expired, displays error message.
            if ($user->getEmailChangeRequestedAt() !== null
                && $user->isEmailChangeRequestRetryDelayExpired($emailChangeRequestRetryDelay) === false) {
                $this->getDoctrine()->getManager()->refresh($user);

                // Displays a flash message informing user that he has to wait $limit minutes between each attempt
                $limit = ceil($emailChangeRequestRetryDelay / 60);
                $errorMessage = '';

                if ($limit < 2) {
                    $errorMessage = $this->get('translator')->trans(
                        'flash.user.verification_link_retry_delay_not_expired_singular'
                    );
                } else {
                    $errorMessage = $this->get('translator')->trans(
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

                $template = $this->render(':Form/User:email-change.html.twig', [
                    'form' => $form->createView()
                ]);
                $jsonTemplate = json_encode($template->getContent());

                return new JsonResponse([
                    'template' => $jsonTemplate
                ], 200);
            }

            $em = $this->getDoctrine()->getManager();

            // Generates email change token and retries if token already exists.
            $loop = true;
            while ($loop) {
                $token = $user->generateSecureToken();

                $duplicate = $em->getRepository('AppBundle:User')->findOneBy(['emailChangeToken' => $token]);
                if (is_null($duplicate)) {
                    $loop = false;
                    $user->setEmailChangeToken($token);
                }
            }

            $user->setEmailChangeRequestedAt(new DateTime());

            // IF email address is not already registered to another account, sends verification email.
            $duplicate = $em->getRepository('AppBundle:User')->findOneBy(['email' => $user->getEmailChangePending()]);
            if (is_null($duplicate)) {
                $emailChangeTokenLifetimeInMinutes = ceil($this->getParameter('email_change_token_lifetime') / 60);
                $this->get('mailer.service')->emailChange($user, $emailChangeTokenLifetimeInMinutes);
            }

            $em->flush();

            $successMessage = $this->render(':FlashAlert/Message/User:email-change-request-success.html.twig', [
                'user' => $user
            ]);
            $this->addFlash(
                'email-change-request-success-raw',
                $successMessage->getContent()
            );

            $template = $this->render(':Form/User:email-change.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        $this->getDoctrine()->getManager()->refresh($user);

        // Renders and json encode the updated form (with errors)
        $template = $this->render(':Form/User:email-change.html.twig', [
            'form' => $form->createView(),
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }

    /**
     * Renders email change confirmation view where user can click a button to confirm or cancel the change.
     *
     * @param Request $request
     * @Route("email-change/confirm", name="email_change_confirm", methods="GET")
     * @return RedirectResponse
     */
    public function confirmAction(Request $request): Response
    {
        $emailChangeToken = $request->get('token');

        if (empty($emailChangeToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')->findOneBy([
            'emailChangeToken' => StringHelper::truncateToMySQLVarcharMaxLength($emailChangeToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'email-change-error',
                $this->get('translator')->trans('flash.user.email_change_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $emailChangeTokenLifetime = $this->getParameter('email_change_token_lifetime');

        if ($user->isEmailChangeTokenExpired($emailChangeTokenLifetime)) {
            $user->setEmailChangePending(null);
            $user->setEmailChangeRequestedAt(null);
            $user->setEmailChangeToken(null);

            $em->flush();

            $this->addFlash(
                'email-change-error',
                $this->get('translator')->trans('flash.user.email_change_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        return $this->render(':User:email-change-confirm.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Cancels email change of account matching token.
     *
     * @param Request $request
     * @Route("/cancel", name="email_change_cancel", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function cancelAction(Request $request): RedirectResponse
    {
        if ($this->isCsrfTokenValid('email_change_cancel', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $emailChangeToken = $request->get('email_change_token');

        if (empty($emailChangeToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')->findOneBy([
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
     * @Route("email-change/change", name="email_change", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function changeAction(Request $request): RedirectResponse
    {
        if ($this->isCsrfTokenValid('email_change', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $emailChangeToken = $request->get('email_change_token');

        if (empty($emailChangeToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')->findOneBy([
            'emailChangeToken' => StringHelper::truncateToMySQLVarcharMaxLength($emailChangeToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'email-change-error',
                $this->get('translator')->trans('flash.user.email_change_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $emailChangeTokenLifetime = $this->getParameter('email_change_token_lifetime');

        if ($user->isEmailChangeTokenExpired($emailChangeTokenLifetime)) {
            $user->setEmailChangePending(null);
            $user->setEmailChangeRequestedAt(null);
            $user->setEmailChangeToken(null);

            $em->flush();

            $this->addFlash(
                'email-change-error',
                $this->get('translator')->trans('flash.user.email_change_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $duplicate = $em->getRepository('AppBundle:User')->findOneBy([
            'email' => $user->getEmailChangePending()
        ]);

        if ($duplicate === null) {
            $user->setEmail($user->getEmailChangePending());
        }

        $user->setEmailChangeToken(null);
        $user->setEmailChangeRequestedAt(null);
        $user->setEmailChangePending(null);
        $em->flush();

        $successMessage = $this->render(':FlashAlert/Message/User:email-change-success.html.twig', [
            'user' => $user
        ]);
        $this->addFlash(
            'email-change-success-raw',
            $successMessage->getContent()
        );

        return $this->redirectToRoute('home');
    }
}
