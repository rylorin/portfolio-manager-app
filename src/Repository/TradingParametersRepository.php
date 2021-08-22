<?php

namespace App\Repository;

use App\Entity\TradingParameters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TradingParameters|null find($id, $lockMode = null, $lockVersion = null)
 * @method TradingParameters|null findOneBy(array $criteria, array $orderBy = null)
 * @method TradingParameters[]    findAll()
 * @method TradingParameters[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradingParametersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradingParameters::class);
    }

    // /**
    //  * @return TradingParameters[] Returns an array of TradingParameters objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TradingParameters
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
