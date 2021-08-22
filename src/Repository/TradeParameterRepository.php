<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TradeParameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TradeParameter|null find($id, $lockMode = null, $lockVersion = null)
 * @method TradeParameter|null findOneBy(array $criteria, array $orderBy = null)
 * @method TradeParameter[]    findAll()
 * @method TradeParameter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradeParameter::class);
    }

    // /**
    //  * @return TradeParameter[] Returns an array of TradeParameter objects
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
    public function findOneBySomeField($value): ?TradeParameter
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
