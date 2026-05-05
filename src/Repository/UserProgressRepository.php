<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserProgress>
 */
class UserProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProgress::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('up')
            ->where('up.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('up.module', 'm')
            ->addSelect('m')
            ->orderBy('up.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countCompletedByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('up')
            ->select('COUNT(up.id)')
            ->where('up.user = :user')
            ->andWhere('up.completed = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
