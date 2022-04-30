<?php

namespace App\Repository;

use App\Entity\CorporateStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CorporateStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method CorporateStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method CorporateStatement[]    findAll()
 * @method CorporateStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CorporateStatementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CorporateStatement::class);
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
