<?php

namespace AppBundle\Security;

use AppBundle\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AccountDeletionLogoutHandler
 *
 * This class is called on logout and will do extra tasks if specific conditions are met. See supports() method for
 * details.
 *
 * Note that this handler requires logout.invalidate_session set to false in firewall settings to work properly.
 * This setting will disable SessionLogoutHandler->logout() which is normally called automatically on logout to
 * invalidate the session.
 * To compensate that, this handler MUST call SessionLogoutHandler->logout() manually to ensure the session is still
 * properly invalidated during the logout process.
 *
 * @package AppBundle\Security
 */
class AccountDeletionLogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var SessionLogoutHandler
     */
    private $sessionLogoutHandler;

    /**
     * @var MailerService
     */
    private $mailer;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EngineInterface
     */
    private $twigEngine;

    /**
     * AccountDeletionLogoutHandler constructor.
     * @param SessionLogoutHandler $sessionLogoutHandler
     * @param MailerService $mailer
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param EngineInterface $twigEngine
     */
    public function __construct(
        SessionLogoutHandler $sessionLogoutHandler,
        MailerService $mailer,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        EngineInterface $twigEngine
    )
    {
        $this->sessionLogoutHandler = $sessionLogoutHandler;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->twigEngine = $twigEngine;
    }

    /**
     * Make sure SessionLogoutHandler->logout() is called here, directly, or indirectly by a method called here.
     *
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        // IF handler doesn't support this request it will only invalidate the session.
        if ($this->supports($request) === false) {
            $this->sessionLogoutHandler->logout($request, $response, $token);
        }

        $session = $request->getSession();

        if ($session->has('account-deletion-request')) {
            $this->userRequestedAccountDeletion($request, $response, $token);

            return;
        }

        if ($session->has('account-deletion-confirmation')) {
            $this->userConfirmedAccountDeletion($request, $response, $token);

            return;
        }

        // For safety, in case $this->supports() wrongly returns true
        $this->sessionLogoutHandler->logout($request, $response, $token);
    }

    /**
     * Checks if handler should do extra tasks during this logout request
     *
     * @param Request $request
     * @return bool
     */
    private function supports(Request $request): bool
    {
        $session = $request->getSession();

        if ($session->has('account-deletion-request') || $session->has('account-deletion-confirmation')) {
            return true;
        }

        return false;
    }

    /**
     * Called when user requests an account deletion email and is therefore logged out.
     * Attaches flash message after authenticated session invalidation to ensure message is displayed once user has been
     * logged out and provided an unauthenticated session.
     *
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    private function userRequestedAccountDeletion(Request $request, Response $response, TokenInterface $token): void
    {
        $user = $token->getUser();

        $successMessage = $this->twigEngine->render(
            ':FlashAlert/Message/User:account-deletion-request-success.html.twig', [
                'user' => $user
            ]
        );

        // Session is properly invalidated
        $this->sessionLogoutHandler->logout($request, $response, $token);

        /*
         * Session has been invalidated so the flash message will be attached to the logged out user session and
         * displayed properly once the user is completely logged out.
         * If the flash message was attached to the session before it has been invalidated, it would never get displayed
         * as it would be destroyed during the invalidation.
         */
        $request->getSession()->getFlashBag()->set(
            'account-deletion-request-success-raw',
            $successMessage
        );
    }

    /**
     * Called when user clicks on account deletion confirmation link sent by email while being simultaneously logged in.
     * Required to prevent 500 error "$user must be an instanceof UserInterface, an object implementing a __toString
     * method, or a primitive string."
     *
     * This crash is maybe caused by a mismatch between session and database.
     *
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    private function userConfirmedAccountDeletion(Request $request, Response $response, TokenInterface $token): void
    {
        $em = $this->entityManager;

        $accountDeletionToken = $request->getSession()->get('account-deletion-confirmation');

        // Session is properly invalidated
        $this->sessionLogoutHandler->logout($request, $response, $token);

        $user = $em->getRepository('AppBundle:User')->findOneBy([
            'accountDeletionToken' => $accountDeletionToken
        ]);

        /*
         * Safety, in case something goes wrong with the account-deletion-confirmation data in session. E.g. if the
         * findOneBy returned the first user with accountDeletionToken to null (in most cases, the admin)
         */
        if (empty($accountDeletionToken) || empty($user) || empty($user->getAccountDeletionToken())) {
            return;
        }

        $em->remove($user);
        $em->flush();

        $this->mailer->accountDeletionSuccess($user);

        $request->getSession()->getFlashBag()->set(
            'account-deletion-success',
            $this->translator->trans('flash.user.account_deletion_success')
        );
    }
}
