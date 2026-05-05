<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function findByModuleOrdered(int $moduleId): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.module = :moduleId')
            ->setParameter('moduleId', $moduleId)
            ->orderBy('q.difficulty', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
