<?php

namespace App\Tests\EventListener;

use App\Tests\AbstractWebTest;
use Exception;

/**
 * Class RedirectIfAuthenticatedTest
 * @package App\Tests\EventListener
 */
class RedirectIfAuthenticatedTest extends AbstractWebTest
{
    /**
     * Tests if public page accessible only while authenticated anonymously returns 302 response to authenticated user.
     *
     * @dataProvider providePublicAnonymousOnlyUrls
     * @param string $method
     * @param string $url
     * @throws Exception
     */
    public function testAnonymousOnlyPageAsAuthenticatedUserIsRedirect(string $method, string $url): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request($method, $url);

        $this->assertTrue($client->getResponse()->isRedirect());
    }

    /**
     * @return array
     */
    public function providePublicAnonymousOnlyUrls(): array
    {
        // ['METHOD', '/path']
        return [
            ['POST', '/activate-account/activate'],
            ['GET', '/activate-account/confirm'],
            ['GET', '/login'],
            ['GET', '/password-reset/request'],
            ['GET', '/password-reset/reset'],
            ['GET', '/register'],
            ['POST', '/register-ajax']
        ];
    }
}
