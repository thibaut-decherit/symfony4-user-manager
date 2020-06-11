<?php

namespace App\Security;

use App\Entity\User;
use App\Helper\StringHelper;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class LoginFormAuthenticator
 *
 * See https://symfony.com/doc/current/security/guard_authentication.html#the-guard-authenticator-methods
 *
 * @package App\Security
 */
class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var MailerService
     */
    private $mailer;

    /**
     * @var Twig
     */
    private $twig;

    /**
     * LoginFormAuthenticator constructor
     *
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param TranslatorInterface $translator
     * @param SessionInterface $session
     * @param MailerService $mailer
     * @param Twig $twig
     */
    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        UserPasswordEncoderInterface $passwordEncoder,
        CsrfTokenManagerInterface $csrfTokenManager,
        TranslatorInterface $translator,
        SessionInterface $session,
        MailerService $mailer,
        Twig $twig
    )
    {
        $this->em = $em;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->translator = $translator;
        $this->session = $session;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function supports(Request $request)
    {
        return 'login' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        $login = StringHelper::truncateToMySQLVarcharMaxLength($request->get('login'));
        $password = StringHelper::truncateToPasswordEncoderMaxLength($request->get('password'));
        $csrfToken = $request->get('_csrf_token');

        if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken('login', $csrfToken))) {
            throw new InvalidCsrfTokenException();
        }

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $login
        );

        return [
            'login' => $login,
            'password' => $password
        ];
    }

    /**
     * This is not limited by UserProvider methods. (e.g UserProvider doesn't have a method to find user by email but
     * guard is still able to do so)
     *
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return User|null
     * @throws Exception
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        $login = $credentials['login'];

        if (preg_match('/^.+@\S+\.\S+$/', $login)) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $login]);
        } else {
            $user = $this->em->getRepository(User::class)->findOneBy([
                'businessUsername' => $login
            ]);
        }

        if (empty($user)) {
            $this->fakeAuthentication($credentials['password']);
        }

        return $user;
    }

    /**
     * If user is not found in database, hashes the password anyway to prevent user enumeration.
     *
     * @param string $password
     * @throws Exception
     */
    private function fakeAuthentication(string $password): void
    {
        $user = new User();
        $this->passwordEncoder->encodePassword($user, $password);
    }

    /**
     * @param mixed $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $password = $credentials['password'];

        if ($this->passwordEncoder->isPasswordValid($user, $password)) {
            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return JsonResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): JsonResponse
    {
        $targetPath = null;
        // if the user hits a secure page and start() was called, this was
        // the URL they were on, and probably where you want to redirect to
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if (!$targetPath) {
            $targetPath = $this->router->generate('home');
        }

        return new JsonResponse([
            'url' => $targetPath
        ], 200);
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        // IF account is not yet activated, send a reminder email with an activation link
        if ($exception instanceof DisabledException) {
            $login = $request->request->get('login');
            $user = null;

            if (preg_match('/^.+@\S+\.\S+$/', $login)) {
                $user = $this->em->getRepository(User::class)->findOneBy([
                    'email' => $login,
                    'activated' => false
                ]);
            } else {
                $user = $this->em->getRepository(User::class)->findOneBy([
                    'businessUsername' => $login,
                    'activated' => false
                ]);
            }

            $this->mailer->loginAttemptOnNonActivatedAccount($user, $request->getLocale());
        }

        $this->session->getFlashBag()->add(
            'login-failed',
            $this->translator->trans('flash.user.invalid_credentials')
        );

        $template = $this->twig->render('form/user/_login.html.twig', [
            'login' => $request->get('login')
        ]);
        $jsonTemplate = json_encode($template);

        return new JsonResponse([
            'template' => $jsonTemplate
        ], 422);
    }

    /**
     * @return string
     */
    protected function getLoginUrl(): string
    {
        return $this->router->generate('login');
    }

    /**
     * This is called if the client accesses a URI/resource that requires authentication, but no authentication details
     * were sent.
     *
     * @param Request $request
     * @param AuthenticationException|null $exception
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $exception = null): RedirectResponse
    {
        // If user is already authenticated, throws 403 error instead of redirecting him to login page.
        if ($exception instanceof AuthenticationException && !$exception->getToken() instanceof AnonymousToken) {
            throw new AccessDeniedHttpException();
        }

        $this->session->getFlashBag()->add(
            'login-required-error',
            $this->translator->trans('flash.user.login_required')
        );
        $url = $this->router->generate('login');

        return new RedirectResponse($url);
    }

    /**
     * If you want to support "remember me" functionality, return true from this method
     *
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return true;
    }
}
