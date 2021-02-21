<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Position;
use App\Entity\Contract;
use App\Entity\Option;
use App\Entity\Stock;
use App\Entity\Portfolio;

/**
 * @method Position|null find($id, $lockMode = null, $lockVersion = null)
 * @method Position|null findOneBy(array $criteria, array $orderBy = null)
 * @method Position[]    findAll()
 * @method Position[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    public function findBySecType(?string $secType, array $criteria = null, array $orderBy = null, int $limit = null, int $offset = null)
    {
        $query =  $this->createQueryBuilder('p')
            ->join('p.portfolio', 'q')
            ->join('p.contract', 'c')
            ;
        if ($secType) {
            $query->andWhere('c INSTANCE OF :type')->setParameter('type', $secType);
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
//                printf('$query->orderBy(%s, %s);\n', $key, $value);
                $query->addOrderBy($key, $value);
            }
        }
        return $query->getQuery()->getResult();
    }

    /*
     * Find all positions related to one stock.
     */
    public function findByStock(Portfolio $portfolio, Stock $stock, $limit = null, $offset = null)
    {
        /* find options positions */
        $results =  $this->createQueryBuilder('p')
          ->leftJoin(Option::class, 'o', 'WITH', 'p.contract = o.id')
          ->andWhere('p.portfolio = :portfolio')->setParameter('portfolio', $portfolio->getId())
          ->andWhere('o.stock = :stock')->setParameter('stock', $stock->getId())
          ->addOrderBy('o.lastTradeDate', 'ASC')
          ->addOrderBy('o.strike', 'ASC')
          ->getQuery()->getResult()
          ;
        if (!$results) $results = [];
        /* find stock position */
        $result =  $this->createQueryBuilder('p')
          ->andWhere('p.portfolio = :portfolio')->setParameter('portfolio', $portfolio->getId())
          ->andWhere('p.contract = :stock')->setParameter('stock', $stock->getId())
          ->getQuery()->getResult()
          ;
        if ($result && $result[0]) array_unshift($results, $result[0]);
        return $results;
    }

    public function findByOption(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $query =  $this->createQueryBuilder('p')
          ->join('p.portfolio', 'q')
          ->join('p.contract', 'c')
          ->join(Option::class, 'o', 'WITH', 'c.id = o.id')
          ->andWhere('c INSTANCE OF :type')
          ->setParameter('type', Contract::TYPE_OPTION)
        ;
        if ($criteria) {
            $i = 0;
            foreach ($criteria as $key => $value) {
                $query->andWhere($key . '= ?' . $i)->setParameter($i, $value);
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

}
