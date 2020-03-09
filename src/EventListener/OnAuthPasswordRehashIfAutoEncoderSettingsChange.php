<?php

namespace App\EventListener;

use App\Model\AbstractUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class OnAuthPasswordRehashIfAutoEncoderSettingsChange
 * @package App\EventListener
 *
 * Automatically upgrades password hash on login to Argon2id as long as user is able to login (works with any algorithm
 * supported by the "auto" encoder (Bcrypt, Argon2i and Argon2id).
 * Automatically upgrades existing Argon2id hashes if options in config/packages/security.yaml are different from
 * current hash.
 */
class OnAuthPasswordRehashIfAutoEncoderSettingsChange
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var int
     */
    private $memoryCost;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var int
     */
    private $timeCost;

    /**
     * OnAuthPasswordRehashIfAutoEncoderSettingsChange constructor.
     * @param EntityManagerInterface $entityManager
     * @param int $memoryCost
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $timeCost
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        int $memoryCost,
        UserPasswordEncoderInterface $passwordEncoder,
        int $timeCost
    )
    {
        $this->entityManager = $entityManager;
        $this->memoryCost = $memoryCost;
        $this->passwordEncoder = $passwordEncoder;
        $this->timeCost = $timeCost;
    }

    /**
     * On authentication checks if user's password needs rehash in case of Argon2 time or memory cost change.
     * WARNING: Will rehash password even if new cost is lower than previous one.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        /*
         * onSecurityInteractiveLogin event is fired not only by a successful login (PostAuthenticationGuardToken) but
         * also by an authentication through remember me token (RememberMeToken).
         * But $event->getRequest()->request->get('password') is obviously empty during the later, thus crashing this
         * event listener.
         */
        if (get_class($event->getAuthenticationToken()) !== PostAuthenticationGuardToken::class) {
            return;
        }

        /**
         * @var AbstractUser $user
         */
        $user = $event->getAuthenticationToken()->getUser();
        $options = [
            'time_cost' => $this->timeCost,
            'memory_cost' => $this->memoryCost
        ];
        $currentHashedPassword = $user->getPassword();

        if (password_needs_rehash($currentHashedPassword, PASSWORD_ARGON2ID, $options)) {
            $em = $this->entityManager;
            $plainPassword = $event->getRequest()->request->get('password');

            $user->setPassword(
                $this->passwordEncoder->encodePassword($user, $plainPassword)
            );

            $em->flush();
        }
    }
}
