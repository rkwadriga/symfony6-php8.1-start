<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;

trait ClearDatabaseTrait
{
    use RefreshTokenTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->clearDatabase();
    }

    protected function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        foreach (Algorithm::cases() as $algorithm) {
            $connection->executeStatement($platform->getTruncateTableSQL($this->getRefreshTokenTableName($algorithm)));
        }
    }
}