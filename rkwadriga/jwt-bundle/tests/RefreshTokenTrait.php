<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Entity\RefreshToken256;
use Rkwadriga\JwtBundle\Entity\RefreshToken512;
use Rkwadriga\JwtBundle\Entity\RefreshTokenEntityInterface;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;

trait RefreshTokenTrait
{
    protected function getRefreshTokenTableName(Algorithm|string $algorithm): string
    {
        if (is_string($algorithm)) {
            $algorithm = Algorithm::from($algorithm);
        }

        return $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_TABLE) . ($algorithm === Algorithm::SHA256 ? '_256' : '_512');
    }

    protected function getRefreshTokenEntityClass(Algorithm|string $algorithm): string
    {
        if (is_string($algorithm)) {
            $algorithm = Algorithm::from($algorithm);
        }

        return $algorithm === Algorithm::SHA256 ? RefreshToken256::class : RefreshToken512::class;
    }

    protected function findRefreshTokenBy(Algorithm|string $algorithm, array $condition): ?RefreshTokenEntityInterface
    {
        return $this->entityManager->getRepository($this->getRefreshTokenEntityClass($algorithm))->findOneBy($condition);
    }

    protected function createRefreshTokenTable(Algorithm|string $algorithm): void
    {
        $table = $this->getRefreshTokenTableName($algorithm);
        $entityClass = $this->getRefreshTokenEntityClass($algorithm);

        // Check if table exist
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        if ($schemaManager->tablesExist([$table])) {
            return;
        }

        // Try to create table with specific table name
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getMetadataFor($entityClass);
        $metadata->setPrimaryTable(['name' => $table]);
        $schemaTool->createSchema([$metadata]);
    }

    protected function saveRefreshToken(Token $refreshToken, mixed $algorithm = null): RefreshTokenEntityInterface
    {
        if ($algorithm === null) {
            $algorithm = Algorithm::from($refreshToken->getHead()['alg']);
        }

        // Create refresh token table name
        $this->createRefreshTokenTable($algorithm);

        // Do not forget to set the correct table name
        $this->setRefreshTokenTableName($algorithm);
        $entityClass = $this->getRefreshTokenEntityClass($algorithm);
        $userID = $this->getConfigDefault(ConfigurationParam::USER_IDENTIFIER);

        $token = new $entityClass($refreshToken->getPayload()[$userID], $refreshToken->getSignature(), $refreshToken->getCreatedAt());
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    protected function clearRefreshTokenTable(mixed $algorithm = null): void
    {
        if ($algorithm === null) {
            $algorithm = $this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM);
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL($this->getRefreshTokenTableName($algorithm)));
    }
}