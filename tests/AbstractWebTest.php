<?php

namespace App\Tests;

use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

/**
 * Class AbstractWebTest
 *
 * Provides methods useful for web tests.
 *
 * @package App\Tests
 */
abstract class AbstractWebTest extends WebTestCase
{
    /**
     * @param bool $catchExceptions
     * @return KernelBrowser
     */
    protected function getAnonymousClient(bool $catchExceptions = true): KernelBrowser
    {
        $client = static::createClient();
        $client->catchExceptions($catchExceptions);

        return $this->setAnonymousUser($client);
    }

    /**
     * Return a guard authenticated client with the given role.
     *
     * @param string $role
     * @param bool $catchExceptions
     * @return KernelBrowser
     * @throws Exception
     */
    protected function getAuthenticatedClient(string $role = 'ROLE_USER', bool $catchExceptions = true): KernelBrowser
    {
        $client = static::createClient();
        $client->catchExceptions($catchExceptions);

        return $this->setGuardAuthenticatedUser($client, $role);
    }

    /**
     * By default, checks if given HTTP status code is not access denied, crash or not found.
     *
     * @param int $statusCode
     * @param array $expectedStatusCodes
     * @return bool
     */
    protected function isExpectedStatusCode(
        int $statusCode,
        array $expectedStatusCodes = [
            200,
            302,
            400,
            422
        ]
    ): bool
    {
        return in_array($statusCode, $expectedStatusCodes);
    }

    /**
     * @param KernelBrowser $client
     * @return KernelBrowser
     */
    private function setAnonymousUser(KernelBrowser $client): KernelBrowser
    {
        $session = $client->getContainer()->get('session');

        /*
         * If you don't define multiple connected firewalls, the context defaults to the firewall name.
         * See https://symfony.com/doc/current/reference/configuration/security.html#firewall-context
         */
        $firewallContext = 'main';

        $token = new AnonymousToken('secret', 'anon.', []);

        $client->getContainer()->get('security.token_storage')->setToken($token);

        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $client;
    }

    /**
     * @param KernelBrowser $client
     * @param string $role
     * @return KernelBrowser
     * @throws Exception
     */
    private function setGuardAuthenticatedUser(KernelBrowser $client, string $role = 'ROLE_USER'): KernelBrowser
    {
        $session = $client->getContainer()->get('session');

        $firewallName = 'main';
        /*
         * If you don't define multiple connected firewalls, the context defaults to the firewall name.
         * See https://symfony.com/doc/current/reference/configuration/security.html#firewall-context
         */
        $firewallContext = 'main';

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('App:User')->findOneUserByRole($role);

        if (is_null($user)) {
            throw new Exception(
                "You need at least one activated User with role $role in your database to run this test"
            );
        }

        $token = new PostAuthenticationGuardToken($user, $firewallName, $user->getRoles());

        $client->getContainer()->get('security.token_storage')->setToken($token);

        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $client;
    }
}
