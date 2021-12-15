<?php
/**
 * Created 2021-12-08
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services\Db;

use Rkwadriga\JwtBundle\Entity\RefreshToken;
use Rkwadriga\JwtBundle\Exception\DbServiceException;

trait ReadQueriesTrait
{
    use BaseQueryTrait;

    private function selectCountForUser(string $userID): int
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        $qb = $this->em->getRepository(RefreshToken::class)->createQueryBuilder('rt');
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

    private function findRecordByPrimaryKey(string $userID, string $refreshToken): ?RefreshToken
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        return $this->em->getRepository(RefreshToken::class)->findOneBy(['userId' => $userID, 'refreshToken' => $refreshToken]);
    }
}