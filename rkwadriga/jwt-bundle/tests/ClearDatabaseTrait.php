<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;

trait ClearDatabaseTrait
{
    use DatabaseTrait;
    use RefreshTokenTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->clearRefreshTokenTables();
    }

    protected function clearRefreshTokenTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        foreach (Algorithm::cases() as $algorithm) {
            if ($this->isRefreshTokenTableExist($algorithm)) {
                $connection->executeStatement($platform->getTruncateTableSQL($this->getRefreshTokenTableName($algorithm)));
            }
        }
    }
}