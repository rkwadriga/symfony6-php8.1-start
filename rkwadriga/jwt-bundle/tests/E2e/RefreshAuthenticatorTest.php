<?php declare(strict_types=1);
/**
 * Created 2021-12-27
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
use Rkwadriga\JwtBundle\Exception\SerializerException;
use Rkwadriga\JwtBundle\Exception\TokenGeneratorException;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
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
        $created = time();
        $algorithm = $this->getDefaultAlgorithm();
        $this->createTokensPair($algorithm, $user->getEmail(), $created, true);

        // Create token params
        $invalidAlgorithm = $algorithm === Algorithm::SHA256 ? Algorithm::SHA512 : Algorithm::SHA256;
        $refreshTokenLifeTime = $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME);
        [$accessTokenParams, $refreshTokenParams] = [
            $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created, $user->getEmail()),
            $this->generateTestTokenParams(TokenType::REFRESH, $algorithm, $created, $user->getEmail())
        ];
        [$accessTokenExpiredParams, $refreshTokenExpiredParams] = [
            $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created - $refreshTokenLifeTime, $user->getEmail()),
            $this->generateTestTokenParams(TokenType::REFRESH, $algorithm, $created - $refreshTokenLifeTime, $user->getEmail())
        ];
        $invalidBase64String = "ûï¾møçž\n";

        // Crete invalid tokens params
        $accessTokenInvalidTypeHead = $this->encodeTokenData(array_merge($accessTokenParams->head, ['sub' => 'INVALID_TOKEN_TYPE']));
        $accessTokenInvalidAlgorithmHead = $this->encodeTokenData(array_merge($accessTokenParams->head, ['alg' => 'INVALID_ALGORITHM']));
        $accessTokenInvalidHeadEncoded = $this->encodeTokenPart(str_replace('{', '[', json_encode($accessTokenParams->head)));
        $accessTokenInvalidHeadDecoded = json_encode($accessTokenParams->head);
        $accessTokenInvalidPayloadEncoded = $this->encodeTokenPart(str_replace('{', '[', json_encode($accessTokenParams->payload)));
        $accessTokenInvalidPayloadDecoded = json_encode($accessTokenParams->payload);
        $accessTokenInvalidHeadAlg = $this->encodeTokenData(array_merge($accessTokenParams->head, ['alg' => $invalidAlgorithm]));

        $refreshTokenInvalidTypeHead = $this->encodeTokenData(array_merge($refreshTokenParams->head, ['sub' => 'INVALID_TOKEN_TYPE']));
        $refreshTokenInvalidAlgorithmHead = $this->encodeTokenData(array_merge($refreshTokenParams->head, ['alg' => 'INVALID_ALGORITHM']));
        $refreshTokenInvalidHeadEncoded = $this->encodeTokenPart(str_replace('{', '[', json_encode($refreshTokenParams->head)));
        $refreshTokenInvalidHeadDecoded = json_encode($refreshTokenParams->head);
        $refreshTokenInvalidPayloadEncoded = $this->encodeTokenPart(str_replace('{', '[', json_encode($refreshTokenParams->payload)));
        $refreshTokenInvalidPayloadDecoded = json_encode($refreshTokenParams->payload);
        $refreshTokenInvalidHeadAlg = $this->encodeTokenData(array_merge($refreshTokenParams->head, ['alg' => $invalidAlgorithm]));

        // Check exceptions
        $testCases = [
            // <--- Access token part --->
            [
                $this->implodeTokenParts($accessTokenInvalidTypeHead, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenGeneratorException::INVALID_TOKEN_TYPE, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidAlgorithmHead, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenGeneratorException::INVALID_ALGORITHM, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidHeadEncoded, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidHeadDecoded, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenInvalidPayloadEncoded, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->contentPart, $invalidBase64String),
                $refreshTokenExpiredParams->tokenString, Response::HTTP_FORBIDDEN, SerializerException::INVALID_BASE64_DATA, 'Invalid base64'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenInvalidPayloadDecoded, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_FORMAT, 'Invalid token format'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenInvalidPayloadDecoded, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_FORMAT, 'Invalid token format'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidHeadAlg, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenExpiredParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_SIGNATURE, 'Invalid token'
            ],
            // <--- /Access token part --->
            // <--- Refresh token part --->
            [
                $this->implodeTokenParts($refreshTokenInvalidTypeHead, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenGeneratorException::INVALID_TOKEN_TYPE, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($refreshTokenInvalidAlgorithmHead, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenGeneratorException::INVALID_ALGORITHM, 'Invalid token head'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenInvalidHeadEncoded, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenInvalidHeadDecoded, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenParams->headString, $refreshTokenInvalidPayloadEncoded, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenParams->contentPart, $invalidBase64String),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_BASE64_DATA, 'Invalid base64'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenParams->headString, $refreshTokenInvalidPayloadDecoded, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_FORMAT, 'Invalid token format'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenParams->headString, $refreshTokenInvalidPayloadDecoded, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_FORMAT, 'Invalid token format'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenInvalidHeadAlg, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_SIGNATURE, 'Invalid token'
            ],
            [
                $accessTokenExpiredParams->tokenString,
                $refreshTokenExpiredParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::REFRESH_TOKEN_EXPIRED, 'Token expired'
            ],
            // <--- /Refresh token part --->
        ];
        foreach ($testCases as $testCase) {
            [$accessToken, $refreshToken, $responseCode, $errorCode, $errorMsg] = $testCase;
            $this->refresh($accessToken, $refreshToken);
            $this->checkErrorResponse($responseCode, $errorMsg, $errorCode);
        }
    }

    public function testTokenValidatorExceptions(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate user
        $user = $this->createUser();

        // Create token pair with expired access token and save refresh token to DB
        $accessTokenLifeTime = $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME);
        $created = time();
        $algorithm = $this->getDefaultAlgorithm();
        $this->createTokensPair($algorithm, $user->getEmail(), $created, true);

        // Create tokens params
        [$accessTokenParams, $refreshTokenParams] = [
            $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created - $accessTokenLifeTime, $user->getEmail()),
            $this->generateTestTokenParams(TokenType::REFRESH, $algorithm, $created, $user->getEmail())
        ];

        // Crete invalid tokens params
        $userIdentifier = $this->getConfigDefault(ConfigurationParam::USER_IDENTIFIER);
        $accessHeadWithoutTokenType = $accessTokenParams->head;
        unset($accessHeadWithoutTokenType['sub']);
        $accessPayloadWithoutUserIdentifier = $accessTokenParams->payload;
        unset($accessPayloadWithoutUserIdentifier[$userIdentifier]);
        $accessTokenHeadInvalidTokenType = $this->encodeTokenData(array_merge($accessTokenParams->head, ['sub' => TokenType::REFRESH->value]));
        $accessTokenHeadWithoutTokenType = $this->encodeTokenData($accessHeadWithoutTokenType);
        $accessTokenHeadEmptyTokenType = $this->encodeTokenData(array_merge($accessTokenParams->head, ['sub' => null]));
        $accessTokenPayloadWithoutUserIdentifier = $this->encodeTokenData($accessPayloadWithoutUserIdentifier);
        $accessTokenPayloadEmptyUserIdentifier = $this->encodeTokenData(array_merge($accessTokenParams->payload, [$userIdentifier => null]));

        $refreshTokenLifeTime = $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME);
        $refreshHeadWithoutTokenType = $refreshTokenParams->head;
        unset($refreshHeadWithoutTokenType['sub']);
        $refreshPayloadWithoutUserIdentifier = $refreshTokenParams->payload;
        unset($refreshPayloadWithoutUserIdentifier[$userIdentifier]);
        $refreshTokenHeadInvalidTokenType = $this->encodeTokenData(array_merge($refreshTokenParams->head, ['sub' => TokenType::ACCESS->value]));
        $refreshTokenHeadWithoutTokenType = $this->encodeTokenData($refreshHeadWithoutTokenType);
        $refreshTokenHeadEmptyTokenType = $this->encodeTokenData(array_merge($refreshTokenParams->head, ['sub' => null]));
        $refreshTokenPayloadWithoutUserIdentifier = $this->encodeTokenData($refreshPayloadWithoutUserIdentifier);
        $refreshTokenPayloadEmptyUserIdentifier = $this->encodeTokenData(array_merge($refreshTokenParams->payload, [$userIdentifier => null]));
        [$expiredAccessTokenParams, $expiredRefreshTokenParams] = [
            $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created - $refreshTokenLifeTime, $user->getEmail()),
            $this->generateTestTokenParams(TokenType::REFRESH, $algorithm, $created - $refreshTokenLifeTime, $user->getEmail())
        ];

        [$invalidUserIdentifierAccessTokenParams, $invalidUserIdentifierRefreshTokenParams] = [
            $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created, $user->getEmail()),
            $this->generateTestTokenParams(TokenType::REFRESH, $algorithm, $created, $user->getEmail() . '_1')
        ];
        [$invalidCreatedAtAccessTokenParams, $invalidCreatedAtRefreshTokenParams] = [
            $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created, $user->getEmail()),
            $this->generateTestTokenParams(TokenType::REFRESH, $algorithm, $created + 1, $user->getEmail())
        ];

        // Check exceptions
        $testCases = [
            // <--- Access token part --->
            [
                $this->implodeTokenParts($accessTokenHeadInvalidTokenType, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token type'
            ],
            [
                $this->implodeTokenParts($accessTokenHeadWithoutTokenType, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenHeadEmptyTokenType, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenPayloadWithoutUserIdentifier, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token payload'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenPayloadEmptyUserIdentifier, $accessTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token payload'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                $refreshTokenParams->tokenString, Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_SIGNATURE, 'Invalid token'
            ],
            // <--- /Access token part --->
            // <--- Refresh token part --->
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenHeadInvalidTokenType, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_REFRESH_TOKEN, 'Invalid token type'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenHeadWithoutTokenType, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_REFRESH_TOKEN, 'Invalid token head'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenHeadEmptyTokenType, $refreshTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_REFRESH_TOKEN, 'Invalid token head'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenParams->headString, $refreshTokenPayloadWithoutUserIdentifier, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_REFRESH_TOKEN, 'Invalid token payload'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenParams->headString, $refreshTokenPayloadEmptyUserIdentifier, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_REFRESH_TOKEN, 'Invalid token payload'
            ],
            [
                $accessTokenParams->tokenString,
                $this->implodeTokenParts($refreshTokenParams->headString, $refreshTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_SIGNATURE, 'Invalid token'
            ],
            [
                $expiredAccessTokenParams->tokenString, $expiredRefreshTokenParams->tokenString,
                Response::HTTP_FORBIDDEN, TokenValidatorException::REFRESH_TOKEN_EXPIRED, 'Token expired'
            ],
            // <--- /Refresh token part --->
            [
                $invalidUserIdentifierAccessTokenParams->tokenString, $invalidUserIdentifierRefreshTokenParams->tokenString,
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_REFRESH_TOKEN, 'Invalid refresh token payload'
            ],
            [
                $invalidCreatedAtAccessTokenParams->tokenString, $invalidCreatedAtRefreshTokenParams->tokenString,
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_REFRESH_TOKEN, 'Invalid refresh token payload'
            ],
        ];
        foreach ($testCases as $testCase) {
            [$accessToken, $refreshToken, $responseCode, $errorCode, $errorMsg] = $testCase;
            $this->refresh($accessToken, $refreshToken);
            $this->checkErrorResponse($responseCode, $errorMsg, $errorCode);
        }
    }

    public function testRefreshTokenDoesNotExistException(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate user
        $user = $this->createUser();

        // Create token pair and save refresh token to DB
        $created = time();
        [$accessToken, $refreshToken] = $this->createTokensPair($this->getDefaultAlgorithm(), $user->getEmail(), $created);

        $this->refresh($accessToken, $refreshToken);
        if ($this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_IN_DB)) {
            $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Refresh token does not exist', TokenValidatorException::INVALID_REFRESH_TOKEN);
        } else {
            $this->assertSame(1, 1);
        }
    }

    public function testUpdatingOldestDbRecord(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate user
        $user = $this->createUser();

        // Check is writing to DB possible
        $limit = $this->getConfigDefault(ConfigurationParam::REFRESH_TOKENS_LIMIT);
        if ($limit === 0 || !$this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_IN_DB)) {
            $this->assertSame(1, 1);
            return;
        }

        // Write refresh tokens to DB
        $algorithm = Algorithm::from($this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM));
        $created = time();
        /** @var ?Token $oldestAccessToken */
        /** @var ?Token $oldestRefreshToken */
        $oldestAccessToken = $oldestRefreshToken = null;
        /** @var array<Token> $notOldestTokens */
        $notOldestTokens = [];
        for ($i = 1; $i <= $limit; $i++) {
            // Create token pair and save refresh token to DB
            [$accessToken, $refreshToken] = $this->createTokensPair($this->getDefaultAlgorithm(), $user->getEmail(), $created + $i + 10, true);
            if ($oldestRefreshToken === null) {
                [$oldestAccessToken, $oldestRefreshToken] = [$accessToken, $refreshToken];
            } else {
                $notOldestTokens[] = $refreshToken;
            }
        }

        // Login the user
        $this->refresh($oldestAccessToken, $oldestRefreshToken);

        // Check that oldest token is not presented in DB
        $oldestTokenInDb = $this->findRefreshTokenBy($algorithm, ['userId' => $user->getEmail(), 'refreshToken' => $oldestRefreshToken->getSignature()]);
        $this->assertNull($oldestTokenInDb);

        // Check token params and check refresh token in DB
        $tokenParts = $this->explodeToken($this->getResponseParams('refreshToken'));
        $signature = $this->decodeTokenPart(end($tokenParts));
        $tokenInDb = $this->findRefreshTokenBy($algorithm, ['userId' => $user->getEmail(), 'refreshToken' => $signature]);
        $this->assertNotNull($tokenInDb);

        // Check that not the oldest tokens are presented in DB
        foreach ($notOldestTokens as $notOldestToken) {
            $tokenInDb = $this->findRefreshTokenBy($algorithm, ['userId' => $user->getEmail(), 'refreshToken' => $notOldestToken->getSignature()]);
            $this->assertNotNull($tokenInDb);
        }
    }

    private function getDefaultAlgorithm(): Algorithm
    {
        return Algorithm::from($this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM));
    }
}