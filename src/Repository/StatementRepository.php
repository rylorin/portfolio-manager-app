<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use \Doctrine\ORM\Query\Expr\Join;
use App\Entity\Portfolio;
use App\Entity\Statement;
use App\Entity\Stock;
use App\Entity\StockTradeStatement;
use App\Entity\OptionTradeStatement;
use App\Entity\Dividend;
use App\Entity\TaxStatement;

/**
 * @method Statement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Statement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Statement[]    findAll()
 * @method Statement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Statement::class);
    }

    public function findSymbolsByPortfolio($portfolio)
    {
//      return $this->findByDate(null, null, [ 'q.portfolio' => $portfolio ]); // il faut restreindre au stocks only
        $query =  $this->createQueryBuilder('q')
            ->addSelect('q')
            ->andWhere('q.portfolio = :portfolio')
            ->setParameter('portfolio', $portfolio)
            ->innerJoin(Stock::class, 's', Join::WITH, 'q.stock = s.id')
            ->orderBy('s.symbol', 'ASC')
          ;
/*
$query =  $this->createQueryBuilder('q')
    ->addSelect('q')
    ->andWhere('q.portfolio = :portfolio')
    ->setParameter('portfolio', $portfolio)

//            ->groupBy('q.stock')
    ->innerJoin(Stock::class, 's', Join::WITH, 'q.stock = s.id')
    ->orderBy('s.symbol', 'ASC')
//            ->addSelect('s.symbol AS symbol')

    ->leftJoin(StockTradeStatement::class, 't', Join::WITH, 'q.id = t.id')
//            ->addSelect('t')
//            ->addSelect('SUM(t.realizedPNL) AS stockPNL')
    ->leftJoin(OptionTradeStatement::class, 'o', Join::WITH, 'q.id = o.id')
//            ->addSelect('o')
//        ->addSelect('SUM(o.realizedPNL) AS optionPNL')
    ->leftJoin(Dividend::class, 'd', Join::WITH, 'q.id = d.id')
//            ->addSelect('d')
//        ->addSelect('SUM(d.amount) AS dividends')
    ->leftJoin(TaxStatement::class, 'x', Join::WITH, 'q.id = x.id')
//            ->addSelect('x')
//        ->addSelect('SUM(x.amount) AS taxes')
  ;
*/
        return $query->getQuery()->getResult();
    }

    public function findByDate(\DateTime $from = null, \DateTime $to = null, array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
      $query =  $this->createQueryBuilder('q');
      if ($from && $to) {
          $query->andWhere('q.date between :from and :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ;
      } elseif ($from) {
          $query->andWhere('q.date >= :from')
            ->setParameter('from', $from)
            ;
      } elseif ($to) {
          $query->andWhere('q.date <= :to')
            ->setParameter('to', $to)
            ;
      }
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
      $query
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ;

      return $query->getQuery()->getResult();
    }

    public function findSummaryByPortfolio($portfolio, \DateTime $fromDate = null)
    {
      return $this->findByDate($fromDate, null, [ 'q.portfolio' => $portfolio ], [ 'q.date' => 'ASC' ]);
      /*
        $query =  $this->createQueryBuilder('q')
            ->addSelect('q')
            ->andWhere('q.portfolio = :portfolio')
            ->setParameter('portfolio', $portfolio)
            ->orderBy('q.date', 'ASC')
          ;
        if ($fromDate) {
            $query->andWhere('q.date >= :from')
              ->setParameter('from', $fromDate)
              ;
        }
        return $query->getQuery()->getResult();
      */
    }

    public function findPreviousStatementForSymbol(Portfolio $portfolio, \DateTime $date, Stock $contract): ?Statement
    {
      $query =  $this->createQueryBuilder('q')
          ->addSelect('q')
          ->andWhere('q.portfolio = :portfolio')
          ->setParameter('portfolio', $portfolio)
          ->andWhere('q.date < :datetime')
          ->setParameter('datetime', $date)
          ->andWhere('q.stock = :contract')
          ->setParameter('contract', $contract)
          ->andWhere('q.tradeUnit IS NOT NULL')
          ->andWhere('q INSTANCE OF App\Entity\StockTradeStatement')
          ->orderBy('q.date', 'DESC')
          ->setMaxResults(1)
        ;
      $results = $query->getQuery()->getResult();
      return array_shift($results);
    }

}
