<?php
/**
 * Created 2021-12-08
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services\Db;

use Exception;
use DateTimeImmutable;
use Rkwadriga\JwtBundle\Entity\RefreshToken;
use Rkwadriga\JwtBundle\Exceptions\DbServiceException;

trait WriteQueriesTrait
{
    use BaseQueryTrait;

    private function deleteOldestRecord(string|int $userID): void
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        try {
            $repository = $this->em->getRepository(RefreshToken::class);
            // Get the oldest created_at
            $qb = $repository->createQueryBuilder('rt');
            $miCreatedAt = $qb
                ->select($qb->expr()->min('rt.createdAt'))
                ->where('rt.userId = :user_id')
                ->setParameter(':user_id', $userID)
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
            if ($miCreatedAt === null) {
                return;
            }

            // Delete the oldest record
            $qb = $repository->createQueryBuilder('rt');
            $qb->delete(RefreshToken::class, 'rt')
                ->where('rt.userId = :user_id AND rt.createdAt = :min_created_at')
                ->setParameter(':user_id', $userID)
                ->setParameter(':min_created_at', $miCreatedAt)
                ->getQuery()
                ->execute();
        } catch (Exception $e) {
            throw new DbServiceException(
                'Sql query error: ' . $e->getMessage(),
                DbServiceException::SQL_ERROR,
                $e
            );
        }
    }

    private function addNewRecord(string|int $userID, string $refreshToken, DateTimeImmutable $createdAt): RefreshToken
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        $refreshToken = new RefreshToken((string) $userID, $refreshToken, $createdAt);
        $this->em->persist($refreshToken);
        $this->em->flush();

        return $refreshToken;
    }
}