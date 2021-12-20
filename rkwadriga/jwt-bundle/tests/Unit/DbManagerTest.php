<?php

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenRefreshingContext;
use Rkwadriga\JwtBundle\Tests\ClearDatabaseTrait;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/DbManagerTest.php
 */
class DbManagerTest extends AbstractUnitTestCase
{
    use ClearDatabaseTrait;

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
            $dbManager = $this->getDbManager($configServiceMock);
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
}
