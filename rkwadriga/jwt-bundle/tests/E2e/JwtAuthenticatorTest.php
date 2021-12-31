<?php declare(strict_types=1);
/**
 * Created 2021-12-31
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/JwtAuthenticatorTest.php
 */
class JwtAuthenticatorTest extends AbstractE2eTestCase
{
    use UserInstanceTrait;
    use InstanceTokenTrait;

    public function testSuccessfulAuthentication(): void
    {
        // Crate user
        $user = $this->createUser();

        // Create token
        $algorithm = $this->getDefaultAlgorithm();
        $token = $this->createToken($algorithm, TokenType::ACCESS, $user->getEmail());

        $this->makeRequest($token->getToken());
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $responseParams = $this->getResponseParams();
        $this->assertIsArray($responseParams);
        $this->assertArrayHasKey('detail', $responseParams);
        $this->assertIsString($responseParams['detail']);
        $this->assertStringContainsStringIgnoringCase('Unable to find the controller', $responseParams['detail']);
    }

    public function testTokenIdentifierExceptions(): void
    {
        // Crate user
        $user = $this->createUser();

        // Create token pair and save refresh token to DB
        $algorithm = $this->getDefaultAlgorithm();
        $token = $this->createToken($algorithm, TokenType::ACCESS, $user->getEmail());

        // Get current values of tokens params locations and names
        [$tokenLocation, $tokenParamName, $tokenType] = [
            $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION),
            $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME),
            $this->getConfigDefault(ConfigurationParam::TOKEN_TYPE),
        ];

        // Set incorrect access token params name
        $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME, $tokenParamName . '_new');
        $this->makeRequest($token->getToken());
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Token not found', TokenIdentifierException::ACCESS_TOKEN_MISSED);
        // Do not forget to set the correct config value
        $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME, $tokenParamName);

        // Set incorrect access token type
        if ($tokenLocation === TokenParamLocation::HEADER->value && $tokenType !== TokenParamType::SIMPLE->value) {
            $this->setConfigDefault(ConfigurationParam::TOKEN_TYPE, TokenParamType::SIMPLE->value);
            $this->makeRequest($token->getToken());
            $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Invalid token', TokenIdentifierException::INVALID_ACCESS_TOKEN);
            $this->setConfigDefault(ConfigurationParam::TOKEN_TYPE, $tokenType);
        }

        // Set incorrect access token location
        /** @var array<TokenParamLocation> $incorrectLocations */
        $incorrectLocations = array_filter(TokenParamLocation::cases(), function (TokenParamLocation $location) use($tokenLocation) {
            return $tokenLocation !== $location->value;
        });
        foreach ($incorrectLocations as $location) {
            $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION, $location->value);
            $this->makeRequest($token->getToken());
            $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Token not found', TokenIdentifierException::ACCESS_TOKEN_MISSED);
        }
        $this->setConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION, $tokenLocation);
    }

    public function testTokenGeneratorExceptions(): void
    {
        // Crate user
        $user = $this->createUser();

        // Create access token
        $created = time();
        $algorithm = $this->getDefaultAlgorithm();

        // Create token params
        $invalidAlgorithm = $algorithm === Algorithm::SHA256 ? Algorithm::SHA512 : Algorithm::SHA256;
        $refreshTokenLifeTime = $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME);
        $accessTokenParams = $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created, $user->getEmail());
        $accessTokenExpiredParams = $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created - $refreshTokenLifeTime, $user->getEmail());
        $invalidBase64String = "ûï¾møçž\n";

        // Crete invalid tokens params
        $accessTokenInvalidTypeHead = $this->encodeTokenData(array_merge($accessTokenParams->head, ['sub' => 'INVALID_TOKEN_TYPE']));
        $accessTokenInvalidAlgorithmHead = $this->encodeTokenData(array_merge($accessTokenParams->head, ['alg' => 'INVALID_ALGORITHM']));
        $accessTokenInvalidHeadEncoded = $this->encodeTokenPart(str_replace('{', '[', json_encode($accessTokenParams->head)));
        $accessTokenInvalidHeadDecoded = json_encode($accessTokenParams->head);
        $accessTokenInvalidPayloadEncoded = $this->encodeTokenPart(str_replace('{', '[', json_encode($accessTokenParams->payload)));
        $accessTokenInvalidPayloadDecoded = json_encode($accessTokenParams->payload);
        $accessTokenInvalidHeadAlg = $this->encodeTokenData(array_merge($accessTokenParams->head, ['alg' => $invalidAlgorithm]));

        // Check exceptions
        $testCases = [
            [
                $this->implodeTokenParts($accessTokenInvalidTypeHead, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenGeneratorException::INVALID_TOKEN_TYPE, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidAlgorithmHead, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenGeneratorException::INVALID_ALGORITHM, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidHeadEncoded, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidHeadDecoded, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenInvalidPayloadEncoded, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_JSON_DATA, 'Invalid json'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->contentPart, $invalidBase64String),
                Response::HTTP_FORBIDDEN, SerializerException::INVALID_BASE64_DATA, 'Invalid base64'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenInvalidPayloadDecoded, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_FORMAT, 'Invalid token format'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenInvalidPayloadDecoded, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_FORMAT, 'Invalid token format'
            ],
            [
                $this->implodeTokenParts($accessTokenInvalidHeadAlg, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_SIGNATURE, 'Invalid token'
            ],
            [
                $accessTokenExpiredParams->tokenString,
                Response::HTTP_UNAUTHORIZED, TokenValidatorException::ACCESS_TOKEN_EXPIRED, 'Token expired'
            ],
        ];
        foreach ($testCases as $testCase) {
            [$accessToken, $responseCode, $errorCode, $errorMsg] = $testCase;
            $this->makeRequest($accessToken);
            $this->checkErrorResponse($responseCode, $errorMsg, $errorCode);
        }
    }

    public function testTokenValidatorExceptions(): void
    {
        // Crate user
        $user = $this->createUser();


        // Create access token
        $created = time();
        $algorithm = $this->getDefaultAlgorithm();

        // Create token params
        $accessTokenLifeTime = $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME);
        $accessTokenParams = $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created, $user->getEmail());
        $refreshTokenParams = $this->generateTestTokenParams(TokenType::REFRESH, $algorithm, $created, $user->getEmail());
        $accessTokenExpiredParams = $this->generateTestTokenParams(TokenType::ACCESS, $algorithm, $created - $accessTokenLifeTime, $user->getEmail());

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

        // Check exceptions
        $testCases = [
            [
                $this->implodeTokenParts($accessTokenHeadInvalidTokenType, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token type'
            ],
            [
                $this->implodeTokenParts($accessTokenHeadWithoutTokenType, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenHeadEmptyTokenType, $accessTokenParams->payloadString, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token head'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenPayloadWithoutUserIdentifier, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token payload'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenPayloadEmptyUserIdentifier, $accessTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_ACCESS_TOKEN, 'Invalid token payload'
            ],
            [
                $this->implodeTokenParts($accessTokenParams->headString, $accessTokenParams->payloadString, $refreshTokenParams->encodedSignature),
                Response::HTTP_FORBIDDEN, TokenValidatorException::INVALID_SIGNATURE, 'Invalid token'
            ],
            [
                $accessTokenExpiredParams->tokenString,
                Response::HTTP_UNAUTHORIZED, TokenValidatorException::ACCESS_TOKEN_EXPIRED, 'Token expired'
            ],
        ];
        foreach ($testCases as $testCase) {
            [$accessToken, $responseCode, $errorCode, $errorMsg] = $testCase;
            $this->makeRequest($accessToken);
            $this->checkErrorResponse($responseCode, $errorMsg, $errorCode);
        }
    }

    private function makeRequest(string $token): void
    {
        $this->setToken($token);
        $this->send('rkwadriga_jwt_test_route');
    }

    private function getDefaultAlgorithm(): Algorithm
    {
        return Algorithm::from($this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM));
    }
}