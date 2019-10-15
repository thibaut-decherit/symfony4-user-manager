<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Entity\User;
use App\Helper\StringHelper;
use App\Model\AbstractUser;
use App\Service\MailerService;
use App\Service\UniqueRandomDataGeneratorService;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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
    public function show(): Response
    {
        return $this->render('user/account_deletion.html.twig');
    }

    /**
     * Sends account deletion link by email to user then log him out.
     *
     * @param Request $request
     * @param MailerService $mailer
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param UniqueRandomDataGeneratorService $uniqueRandomDataGenerator
     * @Route("account/deletion-request", name="account_deletion_request", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException|Exception
     */
    public function request(
        Request $request,
        MailerService $mailer,
        CsrfTokenManagerInterface $csrfTokenManager,
        UniqueRandomDataGeneratorService $uniqueRandomDataGenerator
    ): RedirectResponse
    {
        if ($this->isCsrfTokenValid('account_deletion_request', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        /**
         * @var AbstractUser $user
         */
        $user = $this->getUser();

        $user->setAccountDeletionToken(
            $uniqueRandomDataGenerator->uniqueRandomString(
                User::class,
                'accountDeletionToken'
            )
        );
        $user->setAccountDeletionRequestedAt(new DateTime());

        $accountDeletionTokenLifetimeInMinutes = ceil(
            $this->getParameter('app.account_deletion_token_lifetime') / 60
        );
        $mailer->accountDeletionRequest($user, $accountDeletionTokenLifetimeInMinutes, $request->getLocale());

        $this->getDoctrine()->getManager()->flush();

        /*
         * Confirmation flash alert is handled by App/Security/AccountDeletionLogoutHandler which will retrieve
         * the following session attribute to know it has to do so.
         */
        $this->get('session')->set('account-deletion-request', true);

        /*
         * CSRF protection is enabled for logout so the token must be added to the url or user will get an invalid token
         * exception.
         */
        return $this->redirectToRoute('logout', [
            '_csrf_token' => $csrfTokenManager->getToken('logout')->getValue()
        ]);
    }

    /**
     * Renders account deletion confirmation view where user can click a button to confirm or cancel the deletion.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @Route("/delete-account/confirm", name="account_deletion_confirm", methods="GET")
     * @return RedirectResponse
     */
    public function confirm(Request $request, TranslatorInterface $translator): Response
    {
        $accountDeletionToken = $request->get('token');

        if (empty($accountDeletionToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /**
         * @var User $user
         */
        $user = $em->getRepository(User::class)->findOneBy([
            'accountDeletionToken' => StringHelper::truncateToMySQLVarcharMaxLength($accountDeletionToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'account-deletion-error',
                $translator->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $accountDeletionTokenLifetime = $this->getParameter('app.account_deletion_token_lifetime');

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

        return $this->render('user/account_deletion_confirm.html.twig', [
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
    public function cancel(Request $request): RedirectResponse
    {
        if ($this->isCsrfTokenValid('account_deletion_cancel', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $accountDeletionToken = $request->get('account_deletion_token');

        if (empty($accountDeletionToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /**
         * @var User $user
         */
        $user = $em->getRepository(User::class)->findOneBy([
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
     * @param MailerService $mailer
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @Route("/delete-account/delete", name="account_deletion_delete", methods="POST")
     * @return RedirectResponse
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function delete(
        Request $request,
        TranslatorInterface $translator,
        MailerService $mailer,
        CsrfTokenManagerInterface $csrfTokenManager
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

        /**
         * @var User $user
         */
        $user = $em->getRepository(User::class)->findOneBy([
            'accountDeletionToken' => StringHelper::truncateToMySQLVarcharMaxLength($accountDeletionToken)
        ]);

        if ($user === null) {
            $this->addFlash(
                'account-deletion-error',
                $translator->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $accountDeletionTokenLifetime = $this->getParameter('app.account_deletion_token_lifetime');

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

            /*
             * CSRF protection is enabled for logout so the token must be added to the url or user will get an invalid
             * token exception.
             */
            return $this->redirectToRoute('logout', [
                '_csrf_token' => $csrfTokenManager->getToken('logout')->getValue()
            ]);
        }

        $em = $this->getDoctrine()->getManager();

        $accountDeletionTokenLifetime = $this->getParameter('app.account_deletion_token_lifetime');

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

        $mailer->accountDeletionSuccess($user, $request->getLocale());

        $this->addFlash(
            'account-deletion-success',
            $translator->trans('flash.user.account_deletion_success')
        );

        return $this->redirectToRoute('home');
    }
}
