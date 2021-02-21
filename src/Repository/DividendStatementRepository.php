<?php

declare(strict_types=1);
namespace App\Repository;

use App\Entity\DividendStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DividendStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method DividendStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method DividendStatement[]    findAll()
 * @method DividendStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DividendStatementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DividendStatement::class);
    }

    // /**
    //  * @return DividendStatement[] Returns an array of DividendStatement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DividendStatement
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
