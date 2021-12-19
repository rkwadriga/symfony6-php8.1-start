<?php
/**
 * Created 2021-12-08
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service\Db;

use Rkwadriga\JwtBundle\Entity\RefreshTokenEntityInterface;
use Rkwadriga\JwtBundle\Exception\DbServiceException;

trait ReadQueriesTrait
{
    use BaseQueryTrait;

    private function selectCountForUser(string $userID): int
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        $qb = $this->em->getRepository($this->getEntityClass())->createQueryBuilder('rt');
        try {
            return $qb
                ->select($qb->expr()->count('rt'))
                ->where('rt.userId = :user_id')
                ->setParameter(':user_id', $userID)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            throw new DbServiceException(
                'Sql query error: ' . $e->getMessage(),
                DbServiceException::SQL_ERROR,
                $e
            );
        }
    }

    private function findRecordByPrimaryKey(string $userID, string $refreshToken): ?RefreshTokenEntityInterface
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        return $this->em->getRepository($this->getEntityClass())->findOneBy(['userId' => $userID, 'refreshToken' => $refreshToken]);
    }
}