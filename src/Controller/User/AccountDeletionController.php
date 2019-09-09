<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Helper\StringHelper;
use App\Service\MailerService;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class AccountDeletionController
 * @package App\Controller\User
 */
class AccountDeletionController extends DefaultController
{
    /**
     * Renders account deletion view.
     *
     * @return Response
     */
    public function showAction(): Response
    {
        return $this->render('user/account-deletion.html.twig');
    }

    /**
     * Sends account deletion link by email to user then log him out.
     *
     * @param Request $request
     * @param MailerService $mailerService
     * @Route("account/deletion-request", name="account_deletion_request", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException|Exception
     */
    public function requestAction(Request $request, MailerService $mailerService): RedirectResponse
    {
        if ($this->isCsrfTokenValid('account_deletion_request', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        // Generates account deletion token and retries if token already exists.
        $loop = true;
        while ($loop) {
            $accountDeletionToken = $user->generateSecureToken();

            $duplicate = $em->getRepository('App:User')->findOneBy([
                'accountDeletionToken' => $accountDeletionToken
            ]);

            if (is_null($duplicate)) {
                $loop = false;
                $user->setAccountDeletionToken($accountDeletionToken);
            }
        }

        $user->setAccountDeletionRequestedAt(new DateTime());

        $accountDeletionTokenLifetimeInMinutes = ceil($this->getParameter('account_deletion_token_lifetime') / 60);
        $mailerService->accountDeletionRequest(
            $user, $accountDeletionTokenLifetimeInMinutes,
            $request->getLocale()
        );

        $em->flush();

        /*
         * Confirmation flash alert is handled by App/Security/AccountDeletionLogoutHandler which will retrieve
         * the following session attribute to know it has to do so.
         */
        $this->get('session')->set('account-deletion-request', true);

        return $this->redirectToRoute('logout');
    }

    /**
     * Renders account deletion confirmation view where user can click a button to confirm or cancel the deletion.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @Route("/delete-account/confirm", name="account_deletion_confirm", methods="GET")
     * @return RedirectResponse
     */
    public function confirmAction(Request $request, TranslatorInterface $translator): Response
    {
        $accountDeletionToken = $request->get('token');

        if (empty($accountDeletionToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App:User')->findOneBy([
            'accountDeletionToken' => StringHelper::truncateToMySQLVarcharMaxLength($accountDeletionToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'account-deletion-error',
                $translator->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $accountDeletionTokenLifetime = $this->getParameter('account_deletion_token_lifetime');

        if ($user->isAccountDeletionTokenExpired($accountDeletionTokenLifetime)) {
            $user->setAccountDeletionToken(null);
            $user->setAccountDeletionRequestedAt(null);

            $em->flush();

            $this->addFlash(
                'account-deletion-error',
                $translator->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        return $this->render('user/account-deletion-confirm.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Cancels deletion of account matching deletion token.
     *
     * @param Request $request
     * @Route("/delete-account/cancel", name="account_deletion_cancel", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function cancelAction(Request $request): RedirectResponse
    {
        if ($this->isCsrfTokenValid('account_deletion_cancel', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $accountDeletionToken = $request->get('account_deletion_token');

        if (empty($accountDeletionToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App:User')->findOneBy([
            'accountDeletionToken' => StringHelper::truncateToMySQLVarcharMaxLength($accountDeletionToken)
        ]);

        if ($user !== null) {
            $user->setAccountDeletionToken(null);
            $user->setAccountDeletionRequestedAt(null);

            $em->flush();
        }

        return $this->redirectToRoute('home');
    }

    /**
     * Removes user matching deletion token if token is not expired.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param MailerService $mailerService
     * @Route("/delete-account/delete", name="account_deletion_delete", methods="POST")
     * @return RedirectResponse
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function deleteAction(
        Request $request,
        TranslatorInterface $translator,
        MailerService $mailerService
    ): RedirectResponse
    {
        if ($this->isCsrfTokenValid('account_deletion_delete', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $accountDeletionToken = $request->get('account_deletion_token');

        if (empty($accountDeletionToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App:User')->findOneBy([
            'accountDeletionToken' => StringHelper::truncateToMySQLVarcharMaxLength($accountDeletionToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'account-deletion-error',
                $translator->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $accountDeletionTokenLifetime = $this->getParameter('account_deletion_token_lifetime');

        if ($user->isAccountDeletionTokenExpired($accountDeletionTokenLifetime)) {
            $user->setAccountDeletionToken(null);
            $user->setAccountDeletionRequestedAt(null);

            $em->flush();

            $this->addFlash(
                'account-deletion-error',
                $translator->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $currentUser = $this->getUser();

        /*
         * If user requesting deletion is logged in, he is logged out and account deletion is handled to
         * App/EventListener/AccountDeletionLogoutHandler to prevent 500 error "$user must be an instanceof
         * UserInterface, an object implementing a __toString method, or a primitive string."
         */
        if ($currentUser !== null && $currentUser === $user) {
            $this->get('session')->set('account-deletion-confirmation', $user->getAccountDeletionToken());

            return $this->redirectToRoute('logout');
        }

        $em = $this->getDoctrine()->getManager();

        $accountDeletionTokenLifetime = $this->getParameter('account_deletion_token_lifetime');

        if ($user->isAccountDeletionTokenExpired($accountDeletionTokenLifetime)) {
            $user->setAccountDeletionRequestedAt(null);
            $user->setAccountDeletionToken(null);

            $em->flush();

            $this->addFlash(
                'account-deletion-error',
                $translator->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $em->remove($user);
        $em->flush();

        $mailerService->accountDeletionSuccess($user, $request->getLocale());

        $this->addFlash(
            'account-deletion-success',
            $translator->trans('flash.user.account_deletion_success')
        );

        return $this->redirectToRoute('home');
    }
}
