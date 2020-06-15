<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;

/**
 * Class RememberMeCookieService
 *
 * Allows to validate, generate or set a remember me cookie manually.
 *
 * @package App\Service
 */
class RememberMeCookieService extends TokenBasedRememberMeServices
{
    /**
     * @param Request $request
     * @return bool
     */
    public function hasValidCookie(Request $request): bool
    {
        return $this->autoLogin($request) instanceof RememberMeToken;
    }

    /**
     * Sets remember me cookie in given $response.
     *
     * @param Response $response
     * @param UserInterface $user
     * @param Request $request
     * @return Response
     */
    public function setCookie(Response $response, UserInterface $user, Request $request): Response
    {
        $response->headers->setCookie($this->generateCookie($user, $request));

        return $response;
    }

    /**
     * @param UserInterface $user
     * @param Request $request
     * @return Cookie
     */
    public function generateCookie(UserInterface $user, Request $request): Cookie
    {
        $expires = time() + $this->options['lifetime'];
        $value = $this->generateCookieValue(get_class($user), $user->getUsername(), $expires, $user->getPassword());

        return new Cookie(
            $this->options['name'],
            $value,
            $expires,
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'] ?? $request->isSecure(),
            $this->options['httponly'],
            false,
            $this->options['samesite']
        );
    }
}
