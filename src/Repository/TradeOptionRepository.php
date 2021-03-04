<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OptionTradeStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OptionTradeStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method OptionTradeStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method OptionTradeStatement[]    findAll()
 * @method OptionTradeStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OptionTradeStatement::class);
    }

    // /**
    //  * @return OptionTradeStatement[] Returns an array of OptionTradeStatement objects
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
    public function findOneBySomeField($value): ?OptionTradeStatement
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
