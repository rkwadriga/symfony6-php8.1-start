<?php

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\RefreshTokenEntityInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenRefreshingContext;
use Rkwadriga\JwtBundle\Tests\ClearDatabaseTrait;
use Rkwadriga\JwtBundle\Exception\DbServiceException;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/DbManagerTest.php
 */
class DbManagerTest extends AbstractUnitTestCase
{
    use ClearDatabaseTrait;

    public function testCreateTable(): void
    {
        // Delete tables
        $this->dropRefreshTokenTables();

        foreach (Algorithm::cases() as $algorithm) {
            // Check that table does not exist
            $this->assertFalse($this->isRefreshTokenTableExist($algorithm), "Table for algorithm \"{$algorithm->value}\" should not exist");

            // Mock configuration service to return expected hashing algorithm
            $configValues = [
                ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value,
            ];
            $configServiceMock = $this->mockConfigService($configValues);

            // Create new instance of DBManager - it's constructor should create a table
            $this->createDbManagerInstance($configServiceMock);

            // Check that table exist
            $this->assertTrue($this->isRefreshTokenTableExist($algorithm), "Table for algorithm \"{$algorithm->value}\" should exist");
        }
    }

    public function testWriteRefreshToken(): void
    {
        // Insert tokens for all hashing algorithms and all "refresh" contexts
        $refreshTokens = [];
        foreach (Algorithm::cases() as $algorithm) {
            $configValues = [
                ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value,
                ConfigurationParam::REFRESH_TOKENS_LIMIT->value => 2,
            ];
            $configServiceMock = $this->mockConfigService($configValues);
            $dbManager = $this->createDbManagerInstance($configServiceMock);
            foreach (TokenRefreshingContext::cases() as $refreshContext) {
                $userID = $algorithm->value . '_' .  $refreshContext->value;
                $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID);
                $dbManager->writeRefreshToken($userID, $refreshToken, $refreshContext);
                $refreshTokens[$userID] = $refreshToken;
            }
        }

        // Check tokens in DB
        foreach ($refreshTokens as $userID => $refreshToken) {
            $refreshTokenInDb = $this->findRefreshTokenBy($refreshToken->getHead()['alg'], ['userId' => $userID, 'refreshToken' => $refreshToken->getSignature()]);
            $this->assertNotNull($refreshTokenInDb, "Refresh token for test case \"{$userID}\" not found");
        }
    }

    public function testRefreshTokenLimitExceededException(): void
    {
        foreach (Algorithm::cases() as $algorithm) {
            $testCaseBaseError = "Test testRefreshTokenLimitExceededException \"{$algorithm->value}\" case failed: ";
            $configValues = [
                ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value,
                ConfigurationParam::REFRESH_TOKENS_LIMIT->value => 3,
                ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED->value => false,
            ];
            $configServiceMock = $this->mockConfigService($configValues);
            $dbManager = $this->createDbManagerInstance($configServiceMock);

            $userID = 'test_user_' . $algorithm->value;

            // Create first record - should be ok
            $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID);
            $dbManager->writeRefreshToken($userID, $refreshToken, TokenRefreshingContext::LOGIN);

            // Create second record - should be ok
            $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, time() + 1);
            $dbManager->writeRefreshToken($userID, $refreshToken, TokenRefreshingContext::LOGIN);

            // Create third record - should be ok
            $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, time() + 2);
            $dbManager->writeRefreshToken($userID, $refreshToken, TokenRefreshingContext::LOGIN);

            // Create forth record - should fail
            $exceptionWasThrown = false;
            $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, time() + 3);
            try {
                $dbManager->writeRefreshToken($userID, $refreshToken, TokenRefreshingContext::LOGIN);
            } catch (Exception $e) {
                $exceptionWasThrown = true;
                $this->assertInstanceOf(DbServiceException::class, $e);
                $this->assertSame(DbServiceException::TOKENS_COUNT_EXCEEDED, $e->getCode());
            }
            if (!$exceptionWasThrown) {
                $this->assertEquals(0 ,1, $testCaseBaseError . '"Refresh tokens count exceeded" exception was not thrown');
            }
        }
    }

    public function testFindRefreshToken(): void
    {
        foreach (Algorithm::cases() as $algorithm) {
            $configValues = [
                ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value
            ];
            $configServiceMock = $this->mockConfigService($configValues);
            $dbManager = $this->createDbManagerInstance($configServiceMock);

            $userID = 'test_user_' . $algorithm->value;
            $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID);

            // "findRefreshToken" method should return null
            $this->assertNull($dbManager->findRefreshToken($userID, $refreshToken));

            // Create token record and check again
            $refreshTokenEntityClass = $this->getRefreshTokenEntityClass($algorithm);
            $dbRefreshToken = new $refreshTokenEntityClass($userID, $refreshToken->getSignature(), $refreshToken->getCreatedAt());
            // ...do not forget set a custom table name for entity...
            $this->setRefreshTokenTableName($algorithm);
            $this->entityManager->persist($dbRefreshToken);
            $this->entityManager->flush();

            $refreshTokenFromDb = $dbManager->findRefreshToken($userID, $refreshToken);
            $this->assertNotNull($refreshTokenFromDb);
            $this->assertSame($userID, $refreshTokenFromDb->getUserId());
            $this->assertSame($refreshToken->getSignature(), $refreshTokenFromDb->getRefreshToken());
            $this->assertEquals($refreshToken->getCreatedAt(), $refreshTokenFromDb->getCreatedAt());
        }
    }

    public function testUpdateRefreshToken(): void
    {
        foreach (Algorithm::cases() as $algorithm) {
            $configValues = [
                ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value,
            ];
            $configServiceMock = $this->mockConfigService($configValues);
            $dbManager = $this->createDbManagerInstance($configServiceMock);

            $userID = 'test_user_' . $algorithm->value;
            $createdAt = time();

            // Create "old" and "new" tokens, write the "old"-one to DB
            $oldRefreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, $createdAt);
            $newRefreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, $createdAt + 1);
            $refreshTokenEntityClass = $this->getRefreshTokenEntityClass($algorithm);
            $oldDbRefreshToken = new $refreshTokenEntityClass($userID, $oldRefreshToken->getSignature(), $oldRefreshToken->getCreatedAt());
            // ...do not forget set a custom table name for entity...
            $this->setRefreshTokenTableName($algorithm);
            $this->entityManager->persist($oldDbRefreshToken);
            $this->entityManager->flush();

            // Update tokens record
            $dbManager->updateRefreshToken($userID, $oldRefreshToken, $newRefreshToken);

            // Check that old signature is not presented in DB
            $this->assertNull($this->findRefreshTokenBy($algorithm, ['userId' => $userID, 'refreshToken' => $oldRefreshToken->getSignature()]));

            // Get updated token record from DB and check it
            $newTokenFromDb = $this->findRefreshTokenBy($algorithm, ['userId' => $userID, 'refreshToken' => $newRefreshToken->getSignature()]);
            $this->assertNotNull($newTokenFromDb);
            $this->assertSame($userID, $newTokenFromDb->getUserId());
            $this->assertSame($newRefreshToken->getSignature(), $newTokenFromDb->getRefreshToken());
            $this->assertEquals($newRefreshToken->getCreatedAt(), $newTokenFromDb->getCreatedAt());
        }
    }

    public function testRenewOldestRefreshToken(): void
    {
        foreach (Algorithm::cases() as $algorithm) {
            $configValues = [
                ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value,
                ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED->value => true,
                ConfigurationParam::REFRESH_TOKENS_LIMIT->value => 3
            ];
            $configServiceMock = $this->mockConfigService($configValues);
            $dbManager = $this->createDbManagerInstance($configServiceMock);

            $userID = 'test_user_' . $algorithm->value;
            $createdAt = time();

            // Create and write 3 tokens and remember the first one as "oldest"
            /** @var ?RefreshTokenEntityInterface $oldestDbToken */
            $oldestDbToken = null;
            // ...do not forget set a custom table name for entity...
            $this->setRefreshTokenTableName($algorithm);
            for ($i = 1; $i <= 3; $i++) {
                $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, $createdAt + $i);
                $refreshTokenEntityClass = $this->getRefreshTokenEntityClass($algorithm);
                $newDbRefreshToken = new $refreshTokenEntityClass($userID, $refreshToken->getSignature(), $refreshToken->getCreatedAt());
                if ($oldestDbToken === null) {
                    $oldestDbToken = $newDbRefreshToken;
                }
                $this->entityManager->persist($newDbRefreshToken);
            }
            $this->entityManager->flush();

            // Create new refresh token
            $newRefreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, $createdAt + 4);
            $dbManager->writeRefreshToken($userID, $newRefreshToken, TokenRefreshingContext::LOGIN);

            // Check that oldest refresh token is not presented in DB and the new one is
            $this->assertNull($this->findRefreshTokenBy($algorithm, ['userId' => $userID, 'refreshToken' => $oldestDbToken->getRefreshToken()]));

            $newTokenFromDb = $this->findRefreshTokenBy($algorithm, ['userId' => $userID, 'refreshToken' => $newRefreshToken->getSignature()]);
            $this->assertNotNull($newTokenFromDb);
            $this->assertSame($userID, $newTokenFromDb->getUserId());
            $this->assertSame($newRefreshToken->getSignature(), $newTokenFromDb->getRefreshToken());
            $this->assertEquals($newRefreshToken->getCreatedAt(), $newTokenFromDb->getCreatedAt());
        }
    }

    public function testRefreshTokenMissedException(): void
    {
        foreach (Algorithm::cases() as $algorithm) {
            $testCaseBaseError = "Test testRefreshTokenMissedException \"{$algorithm->value}\" case failed: ";
            $configValues = [
                ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value,
            ];
            $configServiceMock = $this->mockConfigService($configValues);
            $dbManager = $this->createDbManagerInstance($configServiceMock);

            $userID = 'test_user_' . $algorithm->value;

            // Create refresh "old" and "new" refresh tokens
            $exceptionWasThrown = false;
            $oldRefreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID);
            $newRefreshToken = $this->createToken($algorithm, TokenType::REFRESH, $userID, time() + 1);
            try {
                // Try to update the old token
                $dbManager->updateRefreshToken($userID, $oldRefreshToken, $newRefreshToken);
            } catch (Exception $e) {
                $exceptionWasThrown = true;
                $this->assertInstanceOf(DbServiceException::class, $e);
                $this->assertSame(DbServiceException::REFRESH_TOKEN_MISSED, $e->getCode());
            }
            if (!$exceptionWasThrown) {
                $this->assertEquals(0 ,1, $testCaseBaseError . '"Refresh tokens missed" exception was not thrown');
            }
        }
    }
}
