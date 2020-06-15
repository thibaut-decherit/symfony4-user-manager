<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

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
     * @throws NonUniqueResultException
     */
    public function findOneUserByRole(string $role = 'ROLE_USER'): ?User
    {
        $qb = $this->createQueryBuilder('user');

        $qb->where('user.activated = true');

        if ($role !== 'ROLE_USER') {
            $qb
                ->innerJoin('user.roles', 'roles')
                ->andWhere('roles.name = :role')
                ->setParameter('role', $role);
        }

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
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
