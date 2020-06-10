<?php

namespace App\Entity;

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
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends AbstractUser
{
    /**
     * @param string|null $username
     * @return $this
     */
    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param string|null $businessUsername
     * @return $this
     */
    public function setBusinessUsername(?string $businessUsername): self
    {
        $this->businessUsername = $businessUsername;

        return $this;
    }

    /**
     * @param string|null $password
     * @return $this
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
     * @return $this
     */
    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @param string|null $email
     * @return $this
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param string|null $emailChangePending
     * @return $this
     */
    public function setEmailChangePending(?string $emailChangePending): self
    {
        $this->emailChangePending = $emailChangePending;

        return $this;
    }

    /**
     * @param string|null $emailChangeToken
     * @return $this
     */
    public function setEmailChangeToken(?string $emailChangeToken): self
    {
        $this->emailChangeToken = $emailChangeToken;

        return $this;
    }

    /**
     * @param DateTime|null $emailChangeRequestedAt
     * @return $this
     */
    public function setEmailChangeRequestedAt(?DateTime $emailChangeRequestedAt): self
    {
        $this->emailChangeRequestedAt = $emailChangeRequestedAt;

        return $this;
    }

    /**
     * @param string|null $accountDeletionToken
     * @return $this
     */
    public function setAccountDeletionToken(?string $accountDeletionToken): self
    {
        $this->accountDeletionToken = $accountDeletionToken;

        return $this;
    }

    /**
     * @param DateTime|null $accountDeletionRequestedAt
     * @return $this
     */
    public function setAccountDeletionRequestedAt(?DateTime $accountDeletionRequestedAt): self
    {
        $this->accountDeletionRequestedAt = $accountDeletionRequestedAt;

        return $this;
    }

    /**
     * @param string|null $salt
     * @return $this
     */
    public function setSalt(?string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @param UserRole $role
     * @return $this
     */
    public function addRole(UserRole $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
            $role->addUser($this);
        }

        return $this;
    }

    /**
     * @param UserRole $role
     * @return $this
     */
    public function removeRole(UserRole $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            $role->removeUser($this);
        }

        return $this;
    }

    /**
     * @param DateTime|null $registeredAt
     * @return $this
     */
    public function setRegisteredAt(?DateTime $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @param bool|null $activated
     * @return $this
     */
    public function setActivated(?bool $activated): self
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * @param string|null $accountActivationToken
     * @return $this
     */
    public function setAccountActivationToken(?string $accountActivationToken): self
    {
        $this->accountActivationToken = $accountActivationToken;

        return $this;
    }

    /**
     * @param string|null $passwordResetToken
     * @return $this
     */
    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    /**
     * @param DateTime|null $passwordResetRequestedAt
     * @return $this
     */
    public function setPasswordResetRequestedAt(?DateTime $passwordResetRequestedAt): self
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;

        return $this;
    }
}
