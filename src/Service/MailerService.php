<?php

namespace App\Service;

use App\Model\AbstractUser;
use Swift_Mailer;
use Swift_Message;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class MailerService
 * @package App\Service
 */
class MailerService
{
    /**
     * @var string
     */
    private $mailerAddress;

    /**
     * @var string
     */
    private $replyToAddress;

    /**
     * @var Twig
     */
    private $twig;

    /**
     * @var Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * MailerService constructor.
     * @param string $mailerAddress
     * @param string $replyToAddress
     * @param Twig $twig
     * @param Swift_Mailer $swiftMailer
     * @param TranslatorInterface $translator
     */
    public function __construct(
        string $mailerAddress,
        string $replyToAddress,
        Twig $twig,
        Swift_Mailer $swiftMailer,
        TranslatorInterface $translator
    )
    {
        $this->mailerAddress = $mailerAddress;
        $this->replyToAddress = $replyToAddress;
        $this->twig = $twig;
        $this->swiftMailer = $swiftMailer;
        $this->translator = $translator;
    }

    /**
     * Email sent when user requests account deletion.
     *
     * @param AbstractUser $user
     * @param int $accountDeletionTokenLifetimeInMinutes
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function accountDeletionRequest(
        AbstractUser $user,
        int $accountDeletionTokenLifetimeInMinutes,
        string $locale
    ): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/account-deletion-request.html.twig", [
                'user' => $user,
                'accountDeletionTokenLifetimeInMinutes' => $accountDeletionTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.account_deletion_request'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * Email sent when user confirms account deletion.
     *
     * @param AbstractUser $user
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function accountDeletionSuccess(AbstractUser $user, string $locale): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/account-deletion-success.html.twig", [
                'user' => $user,
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.account_deletion_success'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * Email sent when user requests email address change.
     *
     * @param AbstractUser $user
     * @param int $emailChangeTokenLifetimeInMinutes
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function emailChange(AbstractUser $user, int $emailChangeTokenLifetimeInMinutes, string $locale): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/email-address-change.html.twig", [
                'user' => $user,
                'emailChangeTokenLifetimeInMinutes' => $emailChangeTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.email_address_change'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmailChangePending(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function loginAttemptOnNonActivatedAccount(AbstractUser $user, string $locale): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/login-attempt-on-unactivated-account.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.login_attempt'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * Email sent when user requests password reset.
     *
     * @param AbstractUser $user
     * @param int $passwordResetTokenLifetimeInMinutes
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function passwordResetRequest(AbstractUser $user, int $passwordResetTokenLifetimeInMinutes, string $locale): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/password-reset-request.html.twig", [
                'user' => $user,
                'passwordResetTokenLifetimeInMinutes' => $passwordResetTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.password_reset'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function registrationAttemptOnExistingVerifiedEmailAddress(AbstractUser $user, string $locale): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/registration-attempt-on-existing-verified-email-address.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.registration_attempt'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function registrationAttemptOnExistingUnverifiedEmailAddress(AbstractUser $user, string $locale): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/registration-attempt-on-existing-unverified-email-address.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.registration_attempt'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * Email sent after user registration, it contains an activation link.
     *
     * @param AbstractUser $user
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function registrationSuccess(AbstractUser $user, string $locale): void
    {
        $emailBody = $this->twig->render(
            "email/$locale/user/registration-success.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translator->trans('mailer.subjects.welcome'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * @param $subject
     * @param $from
     * @param $to
     * @param $replyToAddress
     * @param $body
     * @param null $attachment
     */
    private function sendEmail($subject, $from, $to, $replyToAddress, $body, $attachment = null): void
    {
        $message = new Swift_Message();

        $message
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setReplyTo($replyToAddress)
            ->setBody($body, 'text/html');
        if ($attachment) {
            $message->attach($attachment);
        }

        $this->swiftMailer->send($message);
    }
}
