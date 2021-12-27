<?php declare(strict_types=1);
/**
 * Created 2021-12-27
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Rkwadriga\JwtBundle\Tests\ClearDatabaseTrait;
use Rkwadriga\JwtBundle\Tests\InstanceTokenTrait;
use Rkwadriga\JwtBundle\Tests\RefreshTokenTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/RefreshAuthenticatorTest.php
 */
class RefreshAuthenticatorTest extends AbstractE2eTestCase
{
    use RefreshTokenTrait;
    use UserInstanceTrait;
    use InstanceTokenTrait;

    public function testSuccessfulRefresh(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate user
        $user = $this->createUser();

        // Create token pair and save refresh token to DB
        [$accessToken, $refreshToken] = $this->createTokensPair($this->getDefaultAlgorithm(), $user->getEmail(), null, true);

        $this->refresh($accessToken, $refreshToken);

        $this->checkTokenResponse($user, Response::HTTP_OK);
    }

    public function testTokenIdentifierExceptions(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate user
        $user = $this->createUser();

        // Create token pair and save refresh token to DB
        [$accessToken, $refreshToken] = $this->createTokensPair($this->getDefaultAlgorithm(), $user->getEmail(), null, true);

        // Get current values of tokens params locations and names
        [$accessTokenParamName, $refreshTokenParamName, $accessTokenLocation, $refreshTokenLocation, $accessTokenType] = [
            $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME),
            $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_PARAM_NAME),
            $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION),
            $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_LOCATION),
            $this->getConfigDefault(ConfigurationParam::TOKEN_TYPE),
        ];

        // Set incorrect access token params name
        $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME, $accessTokenParamName . '_new');
        $this->refresh($accessToken, $refreshToken);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Token not found', TokenIdentifierException::ACCESS_TOKEN_MISSED);
        // Do not forget to set the correct config value
        $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME, $accessTokenParamName);

        // Set incorrect access token params name
        $this->setConfigDefault(ConfigurationParam::REFRESH_TOKEN_PARAM_NAME, $refreshTokenParamName . '_new');
        $this->refresh($accessToken, $refreshToken);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Token not found', TokenIdentifierException::REFRESH_TOKEN_MISSED);
        $this->setConfigDefault(ConfigurationParam::REFRESH_TOKEN_PARAM_NAME, $refreshTokenParamName);

        // Set incorrect access token type
        if ($accessTokenLocation === TokenParamLocation::HEADER->value && $accessTokenType === TokenParamType::BEARER->value) {
            $this->setConfigDefault(ConfigurationParam::TOKEN_TYPE, TokenParamType::SIMPLE->value);
            $this->refresh($accessToken, $refreshToken);
            $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Invalid token', TokenIdentifierException::INVALID_ACCESS_TOKEN);
            $this->setConfigDefault(ConfigurationParam::TOKEN_TYPE, $accessTokenType);
        }

        // Set incorrect access token location
        /** @var array<TokenParamLocation> $incorrectLocations */
        $incorrectLocations = array_filter(TokenParamLocation::cases(), function (TokenParamLocation $location) use($accessTokenLocation) {
            return $accessTokenLocation !== $location->value;
        });
        foreach ($incorrectLocations as $location) {
            $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION, $location->value);
            $this->refresh($accessToken, $refreshToken);
            $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Token not found', TokenIdentifierException::ACCESS_TOKEN_MISSED);
        }
        $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION, $accessTokenLocation);

        // Set incorrect refresh token location
        /** @var array<TokenParamLocation> $incorrectLocations */
        $incorrectLocations = array_filter(TokenParamLocation::cases(), function (TokenParamLocation $location) use($refreshTokenLocation) {
            return $refreshTokenLocation !== $location->value;
        });
        foreach ($incorrectLocations as $location) {
            $this->setConfigDefault(ConfigurationParam::REFRESH_TOKEN_LOCATION, $location->value);
            $this->refresh($accessToken, $refreshToken);
            $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Token not found', TokenIdentifierException::REFRESH_TOKEN_MISSED);
        }
        $this->setConfigDefault(ConfigurationParam::REFRESH_TOKEN_LOCATION, $refreshTokenLocation);
    }

    public function testTokenGeneratorExceptions(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate user
        $user = $this->createUser();

        // Create token pair and save refresh token to DB
        [$accessToken, $refreshToken] = $this->createTokensPair($this->getDefaultAlgorithm(), $user->getEmail(), null, true);

        /** @todo make real test */
        $this->assertSame(1, 1);
    }

    private function getDefaultAlgorithm(): Algorithm
    {
        return Algorithm::getByValue($this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM));
    }
}