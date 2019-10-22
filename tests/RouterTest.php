<?php

namespace App\Tests;

use Exception;

/**
 * Class RouterTest
 *
 * Tests HTTP response of the routes.
 *
 * @package App\Tests
 */
class RouterTest extends AbstractWebTest
{
    /**
     * Tests if public page accessible while authenticated anonymously does not return 500 nor 404 response to
     * anonymous user.
     *
     * @dataProvider providePublicUrls
     * @param $method
     * @param string $url
     */
    public function testPublicPageAsAnonymousUserIsSuccessful(string $method, string $url): void
    {
        $client = $this->getAnonymousClient();

        $client->request($method, $url);
        $statusCode = $client->getResponse()->getStatusCode();

        $this->assertTrue($this->isExpectedStatusCode($statusCode), "Got $statusCode response");
    }

    /**
     * Tests if page only accessible while authenticated redirects anonymous user to login page.
     *
     * @dataProvider providePrivateUrls
     * @param $method
     * @param string $url
     * @throws Exception
     */
    public function testPrivatePageAsAnonymousUserIsRedirectToLogin(string $method, string $url): void
    {
        $client = $this->getAnonymousClient();

        $client->request($method, $url);

        $this->assertTrue($client->getResponse()->isRedirect('/login'));
    }

    /**
     * Tests if page only accessible while authenticated does not return 500 nor 404 response to authenticated user.
     *
     * @dataProvider providePrivateUrls
     * @param $method
     * @param string $url
     * @throws Exception
     */
    public function testPrivatePageAsAuthenticatedUserIsSuccessful(string $method, string $url): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request($method, $url);
        $statusCode = $client->getResponse()->getStatusCode();

        $this->assertTrue($this->isExpectedStatusCode($statusCode), "Got $statusCode response");
    }

    /**
     * @return array
     */
    public function providePrivateUrls(): array
    {
        return [
            ['GET', '/account'],
            ['POST', '/account/account-information-edit-ajax'],
            ['POST', '/account/deletion-request'],
            ['POST', '/account/email-change/request-ajax'],
            ['POST', '/account/password-change-ajax'],
        ];
    }

    /**
     * @return array
     */
    public function providePublicUrls(): array
    {
        // ['METHOD', '/path']
        return [
            ['GET', '/'],
            ['POST', '/activate-account/activate'],
            ['GET', '/activate-account/confirm'],
            ['POST', '/delete-account/cancel'],
            ['GET', '/delete-account/confirm'],
            ['POST', '/delete-account/delete'],
            ['POST', '/email-change/cancel'],
            ['POST', '/email-change/change'],
            ['GET', '/email-change/confirm'],
            ['GET', '/login'],
            ['GET', '/password-reset/request'],
            ['POST', '/password-reset/request'],
            ['GET', '/password-reset/reset'],
            ['POST', '/password-reset/reset'],
            ['GET', '/register'],
            ['POST', '/register-ajax']
        ];
    }
}
