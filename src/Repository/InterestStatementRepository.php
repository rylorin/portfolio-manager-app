<?php

namespace App\Repository;

use App\Entity\InterestStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InterestStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method InterestStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method InterestStatement[]    findAll()
 * @method InterestStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterestStatementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterestStatement::class);
    }

    // /**
    //  * @return InterestStatement[] Returns an array of InterestStatement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InterestStatement
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
