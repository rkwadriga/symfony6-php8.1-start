<?php declare(strict_types=1);
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rkwadriga\JwtBundle\Entity\RefreshToken512;

/**
 * @method RefreshToken512|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefreshToken512|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefreshToken512[]    findAll()
 * @method RefreshToken512[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefreshToken512Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken512::class);
    }
}