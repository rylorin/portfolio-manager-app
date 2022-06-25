<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Option;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Option|null find($id, $lockMode = null, $lockVersion = null)
 * @method Option|null findOneBy(array $criteria, array $orderBy = null)
 * @method Option[]    findAll()
 * @method Option[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Option::class);
    }

    public function findOptions(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $query =  $this->createQueryBuilder('o')
          ->join('o.stock', 's')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ;
        if ($criteria) {
              $i = 0;
              foreach ($criteria as $key => $value) {
                  $query->andWhere($key . ' LIKE ?' . $i)->setParameter($i, '%' . $value . '%');
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

    /*
    public function findAllOption(array $orderBy = null, $limit = null, $offset = null)
    {
        $query =  $this->createQueryBuilder('o')
          ->join('o.stock', 's')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ;
        if ($orderBy) {
            foreach ($orderBy as $key => $value) {
                $query->addOrderBy($key, $value);
            }
        }
        return $query->getQuery()->getResult();
    }
    */

    /**
      * @return Option[] Returns an array of Option objects
      */
    public function findByBeforeLastTradeDate(\DateTime $value = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $query =  $this->createQueryBuilder('o')
          ->join('o.stock', 's')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ;
        if ($value) {
          $query->Where('o.lastTradeDate < ?0')->setParameter(0, $value);
        }
        if ($orderBy) {
            foreach ($orderBy as $key => $value) {
                $query->addOrderBy($key, $value);
            }
        }
        return $query->getQuery()->getResult();
    }

    /**
      * @return Option[] Returns an array of Option objects
      */
      public function findByUnderlying($stock_id)
      {
        return $this->createQueryBuilder('o')
            ->join('o.stock', 's')
            ->Where('s.id = ?0')->setParameter(0, $stock_id)
            ->getQuery()->getResult();
      }

      // /**
    //  * @return Option[] Returns an array of Option objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Option
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
