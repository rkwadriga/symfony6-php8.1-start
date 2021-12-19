<?php
/**
 * Created 2021-12-08
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service\Db;

use Exception;
use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Entity\RefreshTokenEntityInterface;
use Rkwadriga\JwtBundle\Exception\DbServiceException;

trait WriteQueriesTrait
{
    use BaseQueryTrait;

    private function deleteOldestRecord(string $userID): void
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        try {
            $repository = $this->em->getRepository($this->getEntityClass());
            // Get the oldest created_at
            $qb = $repository->createQueryBuilder('rt');
            $oldestRefreshToken = $qb
                ->select('rt.refreshToken')
                ->where('rt.userId = :user_id')
                ->setParameter(':user_id', $userID)
                ->orderBy('rt.createdAt', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
            if ($oldestRefreshToken === null) {
                return;
            }

            // Delete the oldest record
            $qb = $repository->createQueryBuilder('rt');
            $qb->delete($this->getEntityClass(), 'rt')
                ->where('rt.userId = :user_id AND rt.refreshToken = :refresh_token')
                ->setParameter(':user_id', $userID)
                ->setParameter(':refresh_token', $oldestRefreshToken)
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

    private function addNewRecord(string $userID, string $refreshToken, DateTimeImmutable $createdAt): RefreshTokenEntityInterface
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        // If user ID length greater than max field length - hash it by "SHA256" algorithm
        if (strlen($userID) > 64) {
            $userID = hash(Algorithm::SHA256->value, $userID);
        }

        $entityClass = $this->getEntityClass();
        $refreshToken = new $entityClass($userID, $refreshToken, $createdAt);
        $this->em->persist($refreshToken);
        $this->em->flush();

        return $refreshToken;
    }

    private function updateExistedRecord(RefreshTokenEntityInterface $existedToken, string $newRefreshToken, DateTimeImmutable $newCreatedAt): void
    {
        // Do not forget set a custom table name for entity
        $this->setTableName();

        $existedToken
            ->setRefreshToken($newRefreshToken)
            ->setCreatedAt($newCreatedAt);

        $this->em->persist($existedToken);
        $this->em->flush();
    }
}