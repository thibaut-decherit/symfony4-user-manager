<?php

namespace App\Model;

use App\Validator\Constraints as CustomAssert;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
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
 *     fields={"username"},
 *     message="form_errors.user.unique_username",
 *     groups={"Account_Information", "Registration"}
 * )
 */
abstract class AbstractUser implements UserInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
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
    protected $username;

    /**
     * @var string
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
     *     max=50,
     *     minMessage="form_errors.user.password_length",
     *     maxMessage="form_errors.user.password_length",
     *     groups={"Password_Change", "Registration"}
     * )
     * @CustomAssert\BreachedPassword(
     *     groups={"Password_Change", "Registration"}
     * )
     */
    protected $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Registration"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.global.min_length",
     *      maxMessage = "form_errors.global.max_length",
     *      groups={"Registration"}
     * )
     * @Assert\Email(
     *      message = "form_errors.user.valid_email",
     *      checkMX = true,
     *      groups={"Registration"}
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
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.global.min_length",
     *      maxMessage = "form_errors.global.max_length",
     *      groups={"Email_Change"}
     * )
     * @Assert\Email(
     *      message = "form_errors.user.valid_email",
     *      groups={"Email_Change"}
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
     * @var string
     *
     */
    protected $salt;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array")
     */
    protected $roles;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $registeredAt;

    /**
     * @var bool
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

    public function __construct()
    {
        $this->roles = [
            'ROLE_USER'
        ];
        $this->registeredAt = new DateTime();
        $this->activated = false;
    }

    /**
     * @return int
     */
    public function getId(): int
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
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password)
    {
        $this->password = $password;

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
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email)
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
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;

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
     * @param DateTime $registeredAt
     * @return $this
     */
    public function setRegisteredAt(DateTime $registeredAt)
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
     * @param bool $activated
     * @return $this
     */
    public function setActivated(bool $activated)
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

    public function eraseCredentials()
    {
    }

    /**
     * Generates an URI safe base64 encoded string that does not contain "+", "/" or "=" which need to be URL
     * encoded and make URLs unnecessarily longer.
     * With 512 bits of entropy this method will return a string of 86 characters, with 256 bits of entropy it will
     * return 43 characters, and so on.
     *
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public function generateSecureToken(int $entropy = 512): string
    {
        $bytes = random_bytes($entropy / 8);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * @param int $accountDeletionTokenLifetime
     * @return bool
     */
    public function isAccountDeletionTokenExpired(int $accountDeletionTokenLifetime): bool
    {
        return $this->getAccountDeletionRequestedAt()->getTimestamp() + $accountDeletionTokenLifetime < time();
    }

    /**
     * @param int $emailChangeRequestRetryDelay
     * @return bool
     */
    public function isEmailChangeRequestRetryDelayExpired(int $emailChangeRequestRetryDelay): bool
    {
        return $this->getEmailChangeRequestedAt()->getTimestamp() + $emailChangeRequestRetryDelay < time();
    }

    /**
     * @param int $emailChangeTokenLifetime
     * @return bool
     */
    public function isEmailChangeTokenExpired(int $emailChangeTokenLifetime): bool
    {
        return $this->getEmailChangeRequestedAt()->getTimestamp() + $emailChangeTokenLifetime < time();
    }

    /**
     * @param int $passwordResetRequestRetryDelay
     * @return bool
     */
    public function isPasswordResetRequestRetryDelayExpired(int $passwordResetRequestRetryDelay): bool
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetRequestRetryDelay < time();
    }

    /**
     * @param int $passwordResetTokenLifetime
     * @return bool
     */
    public function isPasswordResetTokenExpired(int $passwordResetTokenLifetime): bool
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetTokenLifetime < time();
    }
}
