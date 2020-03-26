<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * Class UserRepository
 * @package App\Repository
 */
class UserRepository extends EntityRepository
{
    /**
     * @param string $role
     * @return User|null
     */
    public function findOneUserByRole(string $role): ?User
    {
        $qb = $this->createQueryBuilder('user');
        $qb
            ->where('user.roles LIKE :roles')
            ->andWhere('user.activated = true')
            ->setParameter('roles', "%$role%")
            ->setMaxResults(1);

        $user = $qb->getQuery()->getResult();

        if (isset($user[0])) {
            return $user[0];
        } else {
            return null;
        }
    }

    /**
     * @param DateTime $minDate
     * @param int $limit
     * @return array
     */
    public function findUnactivatedAccountsOlderThan(DateTime $minDate, int $limit): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb
            ->where('user.activated = false')
            ->andWhere("user.registeredAt < :minDate")
            ->setParameter('minDate', $minDate)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
