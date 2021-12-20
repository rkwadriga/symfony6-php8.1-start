<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;

trait DatabaseTrait
{
    protected function isRefreshTokenTableExist(Algorithm $algorithm): bool
    {
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();

        return $schemaManager->tablesExist([$this->getRefreshTokenTableName($algorithm)]);
    }

    protected function dropRefreshTokenTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        foreach (Algorithm::cases() as $algorithm) {
            if ($this->isRefreshTokenTableExist($algorithm)) {
                $connection->executeStatement($platform->getDropTableSQL($this->getRefreshTokenTableName($algorithm)));
            }
        }
    }

    protected function setRefreshTokenTableName(Algorithm|string $algorithm): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getMetadataFor($this->getRefreshTokenEntityClass($algorithm));
        $metadata->setPrimaryTable(['name' => $this->getRefreshTokenTableName($algorithm)]);
    }
}