<?php
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Exception\DbServiceException;

trait CreateUserTableTrait
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createUserTable();
    }

    private function createUserTable(): void
    {
        // Check if table exist
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        if ($schemaManager->tablesExist(['user'])) {
            return;
        }

        try {
            // Try to create table with specific table name
            $schemaTool = new SchemaTool($this->entityManager);
            $metadata = $this->entityManager->getMetadataFactory()->getMetadataFor(User::class);
            $metadata->setPrimaryTable(['name' => 'user']);
            $schemaTool->createSchema([$metadata]);
        } catch (\Exception $e) {
            throw new DbServiceException(
                sprintf('Can not create table "%s": %s', 'user', $e->getMessage()),
                DbServiceException::CAN_NOT_CREATE_TABLE,
                $e
            );
        }
    }
}