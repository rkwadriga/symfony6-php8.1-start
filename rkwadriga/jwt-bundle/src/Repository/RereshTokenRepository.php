<?php

namespace Rkwadriga\JwtBundle\Repository;

use Rkwadriga\JwtBundle\Entity\RereshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RereshToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method RereshToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method RereshToken[]    findAll()
 * @method RereshToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RereshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RereshToken::class);
    }

    // /**
    //  * @return RereshToken[] Returns an array of RereshToken objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RereshToken
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
