<?php
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Exception\DbServiceException;

trait UserInstanceTrait
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUserTable();
        $this->clearUserTable();
    }

    protected function createUser(?string $email = null, ?string $password = null, array $roles = []): User
    {
        if ($email === null) {
            $email = self::$userID;
        }
        if ($password === null) {
            $password = self::$password;
        }

        $user = new User($email, $password, $roles);
        if (property_exists($this, 'passwordEncoderFactory')) {
            $password = $this->passwordEncoderFactory->getPasswordHasher($user)->hash($password);
            $user->setPassword($password);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
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

    private function clearUserTable(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL('user'));
    }
}