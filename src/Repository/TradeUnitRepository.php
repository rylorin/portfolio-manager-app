<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TradeUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TradeUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method TradeUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method TradeUnit[]    findAll()
 * @method TradeUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradeUnit::class);
    }

    public function findTradeUnits(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $query =  $this->createQueryBuilder('q')
          ->join('q.symbol', 's')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ;
        if ($criteria) {
              $i = 0;
              foreach ($criteria as $key => $value) {
                  $query->andWhere($key . ' = ?' . $i)->setParameter($i, $value);
                  $i++;
              }
        }
        if ($orderBy) {
            foreach ($orderBy as $key => $value) {
                $query->addOrderBy($key, $value);
            }
        }
        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return TradeUnit[] Returns an array of TradeUnit objects
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
    public function findOneBySomeField($value): ?TradeUnit
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
