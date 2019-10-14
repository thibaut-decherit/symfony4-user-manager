<?php

namespace App\Tests;

use Exception;

/**
 * Class GetRouterTest
 *
 * Tests HTTP response of the GET routes.
 *
 * @package App\Tests
 */
class GetRouterTest extends AbstractWebTest
{
    /**
     * Tests if public page accessible while authenticated anonymously does not returns 500 nor 404 response to
     * anonymous user.
     *
     * @dataProvider providePublicUrls
     * @param string $url
     */
    public function testPublicPageAsAnonymousUserIsSuccessful(string $url): void
    {
        $client = $this->getAnonymousClient();

        $client->request('GET', $url);

        $this->assertNotEquals(500, $client->getResponse()->getStatusCode());
        $this->assertNotEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * Tests if page only accessible while authenticated redirects anonymous user to login page.
     *
     * @dataProvider providePrivateUrls
     * @param string $url
     * @throws Exception
     */
    public function testPrivatePageAsAnonymousUserIsRedirectToLogin(string $url): void
    {
        $client = $this->getAnonymousClient();

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isRedirect('/login'));
    }

    /**
     * Tests if page only accessible while authenticated does not returns 500 nor 404 response to authenticated user.
     *
     * @dataProvider providePrivateUrls
     * @param string $url
     * @throws Exception
     */
    public function testPrivatePageAsAuthenticatedUserIsSuccessful(string $url): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', $url);

        $this->assertNotEquals(500, $client->getResponse()->getStatusCode());
        $this->assertNotEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * @return array
     */
    public function providePrivateUrls(): array
    {
        return [
            ['/account']
        ];
    }

    /**
     * @return array
     */
    public function providePublicUrls(): array
    {
        return [
            ['/'],
            ['/activate-account/confirm'],
            ['/delete-account/confirm'],
            ['/email-change/confirm'],
            ['/login'],
            ['/password-reset/request'],
            ['/password-reset/reset'],
            ['/register']
        ];
    }
}
