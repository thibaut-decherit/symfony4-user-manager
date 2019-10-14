<?php

namespace App\Tests\Controller\User;

use App\Tests\AbstractWebTest;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * Class SecurityControllerTest
 * @package App\Tests\Controller\User
 */
class SecurityControllerTest extends AbstractWebTest
{
    /**
     * @throws Exception
     */
    public function testLogoutIsSuccessful(): void
    {
        $client = $this->getAuthenticatedClient();

        $csrfToken =
            $client
                ->getContainer()
                ->get('security.csrf.token_manager')
                ->getToken('logout')
                ->getValue();

        $client->followRedirects();
        $crawler = $client->request('GET', '/logout', [
            '_csrf_token' => $csrfToken
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            'Hi, anonymous visitor', $crawler->filter('.container > p')->first()->text()
        );

        $tokenClass = get_class($client->getContainer()->get('security.token_storage')->getToken());
        $this->assertEquals(AnonymousToken::class, $tokenClass);
    }
}
