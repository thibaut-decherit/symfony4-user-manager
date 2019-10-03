<?php

namespace App\Security;

use App\Model\AbstractUser;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserChecker
 * @package App\Security
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * @param UserInterface $user
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof AbstractUser === false) {
            return;
        }
    }

    /**
     * @param UserInterface $user
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if ($user instanceof AbstractUser === false) {
            return;
        }

        // Throws an exception caught by App\Security\LoginFormAuthenticator::onAuthenticationFailure().
        if ($user->isActivated() === false) {
            throw new DisabledException();
        }
    }
}
