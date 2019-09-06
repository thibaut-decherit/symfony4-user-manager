<?php

namespace App\Service;

use App\Model\AbstractUser;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var EngineInterface
     */
    private $twigEngine;

    /**
     * @var Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @var TranslatorInterface
     */
    private $translatorInterface;

    /**
     * MailerService constructor.
     * @param string $mailerAddress
     * @param string $replyToAddress
     * @param EngineInterface $twigEngine
     * @param Swift_Mailer $swiftMailer
     * @param TranslatorInterface $translatorInterface
     */
    public function __construct(
        string $mailerAddress,
        string $replyToAddress,
        EngineInterface $twigEngine,
        Swift_Mailer $swiftMailer,
        TranslatorInterface $translatorInterface
    )
    {
        $this->mailerAddress = $mailerAddress;
        $this->replyToAddress = $replyToAddress;
        $this->twigEngine = $twigEngine;
        $this->swiftMailer = $swiftMailer;
        $this->translatorInterface = $translatorInterface;
    }

    /**
     * Email sent when user requests account deletion.
     *
     * @param AbstractUser $user
     * @param int $accountDeletionTokenLifetimeInMinutes
     */
    public function accountDeletionRequest(AbstractUser $user, int $accountDeletionTokenLifetimeInMinutes): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/account-deletion-request.html.twig", [
                'user' => $user,
                'accountDeletionTokenLifetimeInMinutes' => $accountDeletionTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.account_deletion_request'),
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
     */
    public function accountDeletionSuccess(AbstractUser $user): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/account-deletion-success.html.twig", [
                'user' => $user,
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.account_deletion_success'),
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
     */
    public function emailChange(AbstractUser $user, int $emailChangeTokenLifetimeInMinutes): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/email-address-change.html.twig", [
                'user' => $user,
                'emailChangeTokenLifetimeInMinutes' => $emailChangeTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.email_address_change'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmailChangePending(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     */
    public function loginAttemptOnNonActivatedAccount(AbstractUser $user): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/login-attempt-on-unactivated-account.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.login_attempt'),
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
     */
    public function passwordResetRequest(AbstractUser $user, int $passwordResetTokenLifetimeInMinutes): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/password-reset-request.html.twig", [
                'user' => $user,
                'passwordResetTokenLifetimeInMinutes' => $passwordResetTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.password_reset'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     */
    public function registrationAttemptOnExistingVerifiedEmailAddress(AbstractUser $user): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/registration-attempt-on-existing-verified-email-address.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.registration_attempt'),
            [$this->mailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyToAddress,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     */
    public function registrationAttemptOnExistingUnverifiedEmailAddress(AbstractUser $user): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/registration-attempt-on-existing-unverified-email-address.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.registration_attempt'),
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
     */
    public function registrationSuccess(AbstractUser $user): void
    {
        $locale = $this->translatorInterface->getLocale();

        $emailBody = $this->twigEngine->render(
            "Email/$locale/User/registration-success.html.twig", [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.welcome'),
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
        $message = Swift_Message::newInstance()
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
