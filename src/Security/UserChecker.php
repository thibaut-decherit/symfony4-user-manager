<?php

namespace App\Security;

use App\Model\AbstractUser;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof AbstractUser) {
            return;
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        if (!$user instanceof AbstractUser) {
            return;
        }

        if ($user->isActivated() === false) {
            throw new DisabledException();
        }
    }
}
