<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Statement;
use App\Entity\OptionTradeStatement;
use App\Entity\Portfolio;
use App\Entity\Option;

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

    public function findPreviousStatementForSymbol(Portfolio $portfolio, \DateTime $date, Option $contract): ?Statement
    {
      $query =  $this->createQueryBuilder('q')
          ->addSelect('q')
          ->andWhere('q.portfolio = :portfolio')
          ->setParameter('portfolio', $portfolio)
          ->andWhere('q.date < :datetime')
          ->setParameter('datetime', $date)
          ->andWhere('q.contract = :contract')
          ->setParameter('contract', $contract)
//          ->andWhere($query->expr()->isNotNull('q.tradeUnit'))
          ->andWhere('q.tradeUnit IS NOT NULL')
          ->andWhere('q INSTANCE OF App\Entity\OptionTradeStatement')
          ->orderBy('q.date', 'DESC')
          ->setMaxResults(1)
        ;
      $results = $query->getQuery()->getResult();
      return array_shift($results);
    }

}
