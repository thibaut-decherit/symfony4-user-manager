<?php

namespace AppBundle\EventListener;

use AppBundle\Helper\StringHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class RedirectIfAuthenticated
 * @package AppBundle\EventListener
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
     * @var RequestStack
     */
    private $request;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * RedirectIfAuthenticated constructor.
     * @param Security $security
     * @param AuthorizationCheckerInterface $authChecker
     * @param RequestStack $request
     * @param RouterInterface $router
     */
    public function __construct(
        Security $security,
        AuthorizationCheckerInterface $authChecker,
        RequestStack $request,
        RouterInterface $router
    )
    {
        $this->security = $security;
        $this->authChecker = $authChecker;
        $this->request = $request;
        $this->router = $router;
    }

    /**
     * Redirects to home if authenticated user attempts to access user management features that should only be available
     * to unauthenticated users (e.g. resetting password, registration, login...).
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        /*
         * $this->security->getToken() === null is needed to prevent "AuthenticationCredentialsNotFoundException
         * (The token storage contains no authentication token. One possible reason may be that there is no firewall
         * configured for this URL.)" when user attempts to access an unknown route.
         * Reason : The kernel is requested multiple times when user requests a route, and during some of
         * those previous kernel requests $this->security->getToken() doesn't yet return a token and the code contained
         * in this listener is executed too early in the "chain" of kernel requests, thus causing the error.
         *
         * $this->request->getCurrentRequest()->get('_controller') === $profilerToolbarAction is needed to ensure
         * profiler requests won't be modified by this listener.
         */
        $profilerToolbarAction = 'web_profiler.controller.profiler:toolbarAction';
        if ($this->security->getToken() === null
            || $this->request->getCurrentRequest()->get('_controller') === $profilerToolbarAction
            || $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED') === false) {
            return;
        }

        $blacklistedRoutes = [
            'account_activation_activate',
            'account_activation_confirm',
            'login',
            'password_reset',
            'password_reset_request',
            'registration',
            'registration_ajax'
        ];

        $requestedRoute = $this->request->getMasterRequest()->get('_route');

        // If requested route is not blacklisted, do nothing.
        if (!in_array($requestedRoute, $blacklistedRoutes)) {
            return;
        }

        $referer = $this->request->getMasterRequest()->headers->get('referer');
        $baseWebsiteUrl = $this->request->getMasterRequest()->getSchemeAndHttpHost();
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
            if (in_array($redirectRoute, $blacklistedRoutes)) {
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
}
