<?php

namespace App\Model;

use App\Entity\UserRole;
use App\Validator\Constraints as CustomAssert;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AbstractUser
 * @package App\Model
 *
 * Abstract class for user classes requiring user management logic:
 *  - registration with activation link sent by email
 *  - login
 *  - username change
 *  - password change
 *  - email address change with verification link sent by email
 *  - password reset
 *  - account deletion with verification link sent by email
 *
 * @UniqueEntity(
 *     fields="businessUsername",
 *     message="form_errors.user.unique_username",
 *     groups={"Account_Information", "Registration"}
 * )
 */
abstract class AbstractUser implements EquatableInterface, UserInterface
{
    /**
     * @var string
     */
    const ROLE_DEFAULT = 'ROLE_USER';

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The string interpreted has username by Symfony. It is used by features like user impersonation and remember me.
     *
     * This property should be considered as an user identifier, NOT as the user's username displayed on the application
     * and used to log-in.
     * Indeed, this username is stored in base64 format in the remember me cookie (which could potentially lead to
     * private data leakage if the cookie is accessed by a third-party and/or malicious individual).
     * Furthermore, if you choose to use it as 'business' username, when an username is changed (e.g. edited by the user
     * himself on the account page) all remember me tokens for this user will be invalidated (because Symfony tries to
     * load the user by reading the base64 encoded username)
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, unique=true)
     */
    protected $username;

    /**
     * The 'business' username of the user, displayed on the application and used to log-in.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Account_Information", "Registration"}
     * )
     * @Assert\Length(
     *     min=2,
     *     max=255,
     *     minMessage="form_errors.global.min_length",
     *     maxMessage="form_errors.global.max_length",
     *     groups={"Account_Information", "Registration"}
     * )
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z0-9]*$/",
     *     message="form_errors.user.alphanumeric_only_username",
     *     groups={"Account_Information", "Registration"}
     * )
     */
    protected $businessUsername;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $password;

    /**
     * Used for model validation. Must not be persisted. Needed to avoid raw password overwriting
     * current user $user->getPassword() when being tested by UserPasswordValidator
     *
     * @var string|null
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Password_Change", "Registration"}
     * )
     * @Assert\Length(
     *     min=8,
     *     max=4096,
     *     minMessage="form_errors.user.password_min_length",
     *     maxMessage="form_errors.user.password_max_length",
     *     groups={"Password_Change", "Registration"}
     * )
     * @CustomAssert\BreachedPassword(
     *     groups={"Password_Change", "Registration"}
     * )
     */
    protected $plainPassword;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Registration"}
     * )
     * @Assert\Length(
     *     min = 2,
     *     max = 255,
     *     minMessage = "form_errors.global.min_length",
     *     maxMessage = "form_errors.global.max_length",
     *     groups={"Registration"}
     * )
     * @Assert\Email(
     *     message = "form_errors.user.valid_email",
     *     groups={"Registration"}
     * )
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Email_Change"}
     * )
     * @Assert\Length(
     *     min = 2,
     *     max = 255,
     *     minMessage = "form_errors.global.min_length",
     *     maxMessage = "form_errors.global.max_length",
     *     groups={"Email_Change"}
     * )
     * @Assert\Email(
     *     message = "form_errors.user.valid_email",
     *     groups={"Email_Change"}
     * )
     */
    protected $emailChangePending;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    protected $emailChangeToken;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $emailChangeRequestedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    protected $accountDeletionToken;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $accountDeletionRequestedAt;

    /**
     * ORM mapping not needed if password hash algorithm generates it's own salt (e.g bcrypt)
     *
     * @var string|null
     *
     */
    protected $salt;

    /**
     * @var Collection|UserRole[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\UserRole", mappedBy="users")
     */
    protected $roles;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime")
     */
    protected $registeredAt;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean")
     */
    protected $activated;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, unique=true, nullable=true)
     */
    protected $accountActivationToken;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    protected $passwordResetToken;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $passwordResetRequestedAt;

    /**
     * AbstractUser constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->registeredAt = new DateTime();
        $this->activated = false;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return $this
     */
    public function setUsername(?string $username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBusinessUsername(): ?string
    {
        return $this->businessUsername;
    }

    /**
     * @param string|null $businessUsername
     * @return $this
     */
    public function setBusinessUsername(?string $businessUsername)
    {
        $this->businessUsername = $businessUsername;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return $this
     */
    public function setPassword(?string $password)
    {
        $this->password = $password;

        // Now that the encrypted password has been set, the plain password can be discarded safely.
        $this->eraseCredentials();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     * @return $this
     */
    public function setPlainPassword(?string $plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return $this
     */
    public function setEmail(?string $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailChangePending(): ?string
    {
        return $this->emailChangePending;
    }

    /**
     * @param string|null $emailChangePending
     * @return $this
     */
    public function setEmailChangePending(?string $emailChangePending)
    {
        $this->emailChangePending = $emailChangePending;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailChangeToken(): ?string
    {
        return $this->emailChangeToken;
    }

    /**
     * @param string|null $emailChangeToken
     * @return $this
     */
    public function setEmailChangeToken(?string $emailChangeToken)
    {
        $this->emailChangeToken = $emailChangeToken;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEmailChangeRequestedAt(): ?DateTime
    {
        return $this->emailChangeRequestedAt;
    }

    /**
     * @param DateTime|null $emailChangeRequestedAt
     * @return $this
     */
    public function setEmailChangeRequestedAt(?DateTime $emailChangeRequestedAt)
    {
        $this->emailChangeRequestedAt = $emailChangeRequestedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountDeletionToken(): ?string
    {
        return $this->accountDeletionToken;
    }

    /**
     * @param string|null $accountDeletionToken
     * @return $this
     */
    public function setAccountDeletionToken(?string $accountDeletionToken)
    {
        $this->accountDeletionToken = $accountDeletionToken;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAccountDeletionRequestedAt(): ?DateTime
    {
        return $this->accountDeletionRequestedAt;
    }

    /**
     * @param DateTime|null $accountDeletionRequestedAt
     * @return $this
     */
    public function setAccountDeletionRequestedAt(?DateTime $accountDeletionRequestedAt)
    {
        $this->accountDeletionRequestedAt = $accountDeletionRequestedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @param string|null $salt
     * @return $this
     */
    public function setSalt(?string $salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @return Collection|UserRole[]
     */
    public function getRolesCollection(): Collection
    {
        return $this->roles;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $rolesNames = [];

        foreach ($roles as $role) {
            $rolesNames[] = $role->getName();
        }

        // Adds the default role.
        $rolesNames[] = static::ROLE_DEFAULT;

        return array_unique($rolesNames);
    }

    /**
     * @param UserRole $role
     * @return $this
     */
    public function addRole(UserRole $role)
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
    public function removeRole(UserRole $role)
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            $role->removeUser($this);
        }

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRegisteredAt(): DateTime
    {
        return $this->registeredAt;
    }

    /**
     * @param DateTime|null $registeredAt
     * @return $this
     */
    public function setRegisteredAt(?DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * @param bool|null $activated
     * @return $this
     */
    public function setActivated(?bool $activated)
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountActivationToken(): ?string
    {
        return $this->accountActivationToken;
    }

    /**
     * @param string|null $accountActivationToken
     * @return $this
     */
    public function setAccountActivationToken(?string $accountActivationToken)
    {
        $this->accountActivationToken = $accountActivationToken;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    /**
     * @param string|null $passwordResetToken
     * @return $this
     */
    public function setPasswordResetToken(?string $passwordResetToken)
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getPasswordResetRequestedAt(): ?DateTime
    {
        return $this->passwordResetRequestedAt;
    }

    /**
     * @param DateTime|null $passwordResetRequestedAt
     * @return $this
     */
    public function setPasswordResetRequestedAt(?DateTime $passwordResetRequestedAt)
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;

        return $this;
    }

    /**
     * Compares user stored in session ($this) with his entry stored in database (UserInterface $user).
     * IF it returns false, user is logged out.
     *
     * Useful e.g. to prevent user from retaining access rights even if relevant role has been removed from his database
     * entry.
     * Example: user logs in while having ROLE_RESTRICTED in his database entry, then an admin removes this role,
     * without this check the application still considers the user has ROLE_RESTRICTED until he logs out.
     *
     * See https://symfony.com/doc/current/security/user_provider.html#understanding-how-users-are-refreshed-from-the-session
     *
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        if ($this->getRoles() !== $user->getRoles()) {
            return false;
        }

        return true;
    }

    public function eraseCredentials(): void
    {
        $this->setPlainPassword(null);
    }

    /**
     * @param int $accountDeletionTokenLifetime
     * @return bool
     */
    public function isAccountDeletionTokenExpired(int $accountDeletionTokenLifetime): bool
    {
        if (is_null($this->getAccountDeletionRequestedAt())) {
            return true;
        }

        return ($this->getAccountDeletionRequestedAt()->getTimestamp() + $accountDeletionTokenLifetime) < time();
    }

    /**
     * @param int $emailChangeRequestRetryDelay
     * @return bool
     */
    public function isEmailChangeRequestRetryDelayExpired(int $emailChangeRequestRetryDelay): bool
    {
        if (is_null($this->getEmailChangeRequestedAt())) {
            return true;
        }

        return ($this->getEmailChangeRequestedAt()->getTimestamp() + $emailChangeRequestRetryDelay) < time();
    }

    /**
     * @param int $emailChangeTokenLifetime
     * @return bool
     */
    public function isEmailChangeTokenExpired(int $emailChangeTokenLifetime): bool
    {
        if (is_null($this->getEmailChangeRequestedAt())) {
            return true;
        }

        return ($this->getEmailChangeRequestedAt()->getTimestamp() + $emailChangeTokenLifetime) < time();
    }

    /**
     * @param int $passwordResetRequestRetryDelay
     * @return bool
     */
    public function isPasswordResetRequestRetryDelayExpired(int $passwordResetRequestRetryDelay): bool
    {
        if (is_null($this->getPasswordResetRequestedAt())) {
            return true;
        }

        return ($this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetRequestRetryDelay) < time();
    }

    /**
     * @param int $passwordResetTokenLifetime
     * @return bool
     */
    public function isPasswordResetTokenExpired(int $passwordResetTokenLifetime): bool
    {
        if (is_null($this->getPasswordResetRequestedAt())) {
            return true;
        }

        return ($this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetTokenLifetime) < time();
    }
}
