<?php

namespace App\Entity;

use App\Helper\StringHelper;
use App\Model\AbstractUser;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class User
 * @package App\Entity
 *
 * Class extending Model\AbstractUser which contains everything required to manage an user account so you can focus on
 * business and project specific attributes and functions.
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends AbstractUser
{
    /**
     * @param string|null $username
     * @return User
     */
    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param string|null $businessUsername
     * @return User
     */
    public function setBusinessUsername(?string $businessUsername): self
    {
        $this->businessUsername = $businessUsername;

        return $this;
    }

    /**
     * @param string|null $password
     * @return User
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        // Now that the encrypted password has been set, the plain password can be discarded safely.
        $this->eraseCredentials();

        return $this;
    }

    /**
     * @param string|null $plainPassword
     * @return User
     */
    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @param string|null $email
     * @return User
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param string|null $emailChangePending
     * @return User
     */
    public function setEmailChangePending(?string $emailChangePending): self
    {
        $this->emailChangePending = $emailChangePending;

        return $this;
    }

    /**
     * @param string|null $emailChangeToken
     * @return User
     */
    public function setEmailChangeToken(?string $emailChangeToken): self
    {
        $this->emailChangeToken = $emailChangeToken;

        return $this;
    }

    /**
     * @param DateTime|null $emailChangeRequestedAt
     * @return User
     */
    public function setEmailChangeRequestedAt(?DateTime $emailChangeRequestedAt): self
    {
        $this->emailChangeRequestedAt = $emailChangeRequestedAt;

        return $this;
    }

    /**
     * @param string|null $accountDeletionToken
     * @return User
     */
    public function setAccountDeletionToken(?string $accountDeletionToken): self
    {
        $this->accountDeletionToken = $accountDeletionToken;

        return $this;
    }

    /**
     * @param DateTime|null $accountDeletionRequestedAt
     * @return User
     */
    public function setAccountDeletionRequestedAt(?DateTime $accountDeletionRequestedAt): self
    {
        $this->accountDeletionRequestedAt = $accountDeletionRequestedAt;

        return $this;
    }

    /**
     * @param string|null $salt
     * @return User
     */
    public function setSalt(?string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): self
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * @param string $role
     * @return User
     */
    public function addRole(string $role): self
    {
        $role = StringHelper::strToUpper($role);

        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->getRoles(), true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * @param string $role
     * @return User
     */
    public function removeRole(string $role): self
    {
        $role = StringHelper::strToUpper($role);

        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        $key = array_search($role, $this->roles, true);
        if ($key !== false) {
            unset($this->roles[$key]);
        }

        return $this;
    }

    /**
     * @param DateTime $registeredAt
     * @return User
     */
    public function setRegisteredAt(DateTime $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @param bool $activated
     * @return User
     */
    public function setActivated(bool $activated): self
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * @param string|null $accountActivationToken
     * @return User
     */
    public function setAccountActivationToken(?string $accountActivationToken): self
    {
        $this->accountActivationToken = $accountActivationToken;

        return $this;
    }

    /**
     * @param string|null $passwordResetToken
     * @return User
     */
    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    /**
     * @param DateTime|null $passwordResetRequestedAt
     * @return User
     */
    public function setPasswordResetRequestedAt(?DateTime $passwordResetRequestedAt): self
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;

        return $this;
    }
}
