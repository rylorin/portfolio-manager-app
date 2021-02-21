<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TradeOptionStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TradeOptionStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method TradeOptionStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method TradeOptionStatement[]    findAll()
 * @method TradeOptionStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradeOptionStatement::class);
    }

    // /**
    //  * @return TradeOptionStatement[] Returns an array of TradeOptionStatement objects
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
    public function findOneBySomeField($value): ?TradeOptionStatement
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
