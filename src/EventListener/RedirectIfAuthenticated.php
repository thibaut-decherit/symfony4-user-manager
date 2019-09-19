<?php

namespace App\EventListener;

use App\Helper\StringHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class RedirectIfAuthenticated
 * @package App\EventListener
 */
class RedirectIfAuthenticated
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $blacklistedRoutes;

    /**
     * RedirectIfAuthenticated constructor.
     * @param Security $security
     * @param AuthorizationCheckerInterface $authChecker
     * @param RouterInterface $router
     */
    public function __construct(
        Security $security,
        AuthorizationCheckerInterface $authChecker,
        RouterInterface $router
    )
    {
        $this->security = $security;
        $this->authChecker = $authChecker;
        $this->router = $router;
        $this->blacklistedRoutes = [
            'account_activation_activate',
            'account_activation_confirm',
            'login',
            'password_reset',
            'password_reset_request',
            'registration',
            'registration_ajax'
        ];
    }

    /**
     * Redirects to home if authenticated user attempts to access user management features that should only be available
     * to unauthenticated users (e.g. resetting password, registration, login...).
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->supports($event) === false) {
            return;
        }

        $referer = $event->getRequest()->headers->get('referer');
        $baseWebsiteUrl = $event->getRequest()->getSchemeAndHttpHost();
        $previousUrl = '';

        /*
         * IF refer url exists and starts with base website url, the latter is removed from referer url so router can
         * match result to existing route.
         */
        if (is_string($referer) && StringHelper::startsWith($referer, $baseWebsiteUrl)) {
            $previousUrl = explode($baseWebsiteUrl, $referer)[1];

            // Removes potential query string
            $previousUrl = explode('?', $previousUrl)[0];
        }

        /*
         * Tries to redirect to route matching $previousUrl. If no match is found (most likely because $referer url
         * comes from another website), it will throw ResourceNotFoundException.
         * If $referer url comes from our website but contains mandatory parameter(s), it will throw
         * MissingMandatoryParametersException.
         * If no match is found, it redirects to home.
         */
        try {
            $redirectRoute = $this->router->getMatcher()->match($previousUrl)['_route'];

            // If referer url matches one of the blacklisted routes, redirect to home to prevent redirect loop.
            if (in_array($redirectRoute, $this->blacklistedRoutes)) {
                $url = $this->router->generate('home');
            } else {
                $url = $this->router->generate($redirectRoute);
            }

            // Must be able to catch at least ResourceNotFoundException and MissingMandatoryParametersException.
        } catch (Exception $exception) {
            $url = $this->router->generate('home');
        }

        $event->setResponse(new RedirectResponse($url));
    }

    /**
     * @param RequestEvent $event
     * @return bool
     */
    private function supports(RequestEvent $event): bool
    {
        /*
         * Required to prevent profiler error "An error occurred while loading the web debug toolbar."
         * Possible reason: The kernel is requested multiple times when user requests a route, and during some of
         * those requests $this->security->getToken() doesn't yet return a token and the code contained
         * in this listener is executed too early in the "chain" of kernel requests, thus causing the error.
         */
        if (is_null($this->security->getToken())) {
            return false;
        }

        // Required to ensure profiler requests won't be modified by this listener.
        if ($event->getRequest()->get('_controller') === 'web_profiler.controller.profiler:toolbarAction') {
            return false;
        }

        /*
         * Required to avoid wasting resources by triggering the listener on sub-requests (e.g. when embedding
         * controllers in templates).
         *
         * Could also help to prevent "AuthenticationCredentialsNotFoundException (The token storage contains no
         * authentication token. One possible reason may be that there is no firewall configured for this URL.)"
         * when user attempts to access an unknown route.
         * Possible reason: The kernel is requested multiple times when user requests a route, and during some of
         * those previous kernel requests $this->security->getToken() doesn't yet return a token and the code contained
         * in this listener is executed too early in the "chain" of kernel requests, thus causing the error.
         */
        if ($event->isMasterRequest() === false) {
            return false;
        }

        // Anonymous users should obviously not be redirected.
        if ($this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED') === false) {
            return false;
        }

        // If requested route is not blacklisted, no redirect.
        if (!in_array($event->getRequest()->get('_route'), $this->blacklistedRoutes)) {
            return false;
        }

        return true;
    }
}
