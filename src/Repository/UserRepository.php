<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

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
