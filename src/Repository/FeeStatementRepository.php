<?php

namespace App\Repository;

use App\Entity\FeeStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeeStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeeStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeeStatement[]    findAll()
 * @method FeeStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeeStatementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeeStatement::class);
    }

    // /**
    //  * @return FeeStatement[] Returns an array of FeeStatement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FeeStatement
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
