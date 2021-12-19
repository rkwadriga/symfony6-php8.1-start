<?php

namespace Rkwadriga\JwtBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rkwadriga\JwtBundle\Entity\RefreshToken256;

/**
 * @method RefreshToken256|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefreshToken256|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefreshToken256[]    findAll()
 * @method RefreshToken256[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefreshToken256Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken256::class);
    }
}
