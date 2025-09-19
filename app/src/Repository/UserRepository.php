<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
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

    // Exemple : trouver tous les étudiants d’un niveau donné
    public function findByStudyLevel(string $level): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.study_level = :level')
            ->setParameter('level', $level)
            ->orderBy('s.fullname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Exemple : trouver les étudiants par tranche d’âge
    public function findByAgeRange(int $min, int $max): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.age BETWEEN :min AND :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->orderBy('s.age', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
