<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.category = :category')
            ->setParameter('category', $category)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDifficulty(string $difficulty): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.difficulty = :difficulty')
            ->setParameter('difficulty', $difficulty)
            ->orderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
