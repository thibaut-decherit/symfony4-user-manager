<?php

namespace App\Repository;

use App\Entity\User;
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
     * @param int $days
     * @param int $limit
     * @return array
     */
    public function findUnactivatedAccountsOlderThan(int $days, int $limit): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb
            ->where('user.activated = false')
            ->andWhere("user.registeredAt < DATE_SUB(CURRENT_TIME(), :days, 'day')")
            ->setParameter('days', $days)
            ->setMaxResults($limit);

        $users = $qb->getQuery()->getResult();

        return $users;
    }
}
