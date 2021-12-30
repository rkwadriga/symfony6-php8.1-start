<?php declare(strict_types=1);
/**
 * Created 2021-12-21
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;
use Rkwadriga\JwtBundle\Exception\TokenGeneratorException;
use Rkwadriga\JwtBundle\Service\TokenGenerator;
use Rkwadriga\JwtBundle\Tests\Entity\TokenTestPramsEntity;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/TokenGeneratorTest.php
 */
class TokenGeneratorTest extends AbstractUnitTestCase
{
    public function testFromPayload(): void
    {
        // For all token types...
        foreach (TokenType::cases() as $tokenType) {
            // For all algorithms...
            foreach (Algorithm::cases() as $algorithm) {
                // Create control token params
                $controlToken = $this->generateTestTokenParams($tokenType, $algorithm);

                // Mock services...
                // ... ConfigService mock
                $configServiceMock = $this->mockConfigService([ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value]);
                // ... SerializerService mock
                $serializerServiceMock = $this->mockSerializerService([
                    'serialize' => ['__map' => [[$controlToken->head, $controlToken->headString], [$controlToken->payload, $controlToken->payloadString]]],
                    'implode' => ['__map' => [
                        [$controlToken->headString, $controlToken->payloadString, $controlToken->contentPart],
                        [$controlToken->contentPart, $controlToken->encodedSignature, $controlToken->tokenString]
                    ]],
                    'signature' => $controlToken->signature,
                    'encode' => $controlToken->encodedSignature,
                ]);
                // ... HeadGeneratorService mock
                $headGeneratorServiceMock = $this->mockHeadGeneratorService(['generate' => $controlToken->head]);

                // Create "TokenGenerator" service instances with mocked config value for algorithm and without it
                /** @var array<TokenGenerator> $tokenGenerators */
                $tokenGenerators = [
                    'mocked_algorithm' => $this->createTokenGeneratorInstance($configServiceMock, $serializerServiceMock, $headGeneratorServiceMock),
                    'given_algorithm' => $this->createTokenGeneratorInstance($this->mockConfigService(), $serializerServiceMock, $headGeneratorServiceMock)
                ];

                // Test both variants of configuring algorithm
                foreach ($tokenGenerators as $algorithmGivenType => $tokenGenerator) {
                    // For all token creation contexts...
                    foreach (TokenCreationContext::cases() as $tokenCreationContext) {
                        $testCaseBaseError = "Test testFromPayload, case \"{$tokenType->value}_{$algorithm->value}_{$tokenCreationContext->value}\" failed: ";

                        // Generate token
                        if ($algorithmGivenType === 'mocked_algorithm') {
                            $token = $tokenGenerator->fromPayload($controlToken->payload, $tokenType, $tokenCreationContext);
                        } else {
                            $token = $tokenGenerator->fromPayload($controlToken->payload, $tokenType, $tokenCreationContext, $algorithm);
                        }

                        // Check token params
                        $this->checkTokenParams($token, $controlToken, $tokenType, $testCaseBaseError);
                    }
                }
            }
        }
    }

    public function testFromString(): void
    {
        // For all token types...
        foreach (TokenType::cases() as $tokenType) {
            // For all algorithms...
            foreach (Algorithm::cases() as $algorithm) {
                // Create control token params
                $controlToken = $this->generateTestTokenParams($tokenType, $algorithm);
                $invalidTypeHead = array_merge($controlToken->head, ['sub' => 'INVALID_TOKEN_TYPE']);
                $tokenWithInvalidType = $this->implodeTokenParts('__invalid_token_type_head', $controlToken->payloadString, $controlToken->encodedSignature);
                $invalidAlgorithmHead = array_merge($controlToken->head, ['alg' => 'INVALID_ALGORITHM']);
                $tokenWithInvalidAlgorithm = $this->implodeTokenParts('__invalid_algorithm_head', $controlToken->payloadString, $controlToken->encodedSignature);

                // ... SerializerService mock
                $serializerServiceMock = $this->mockSerializerService([
                    'explode' => ['__map' => [
                        [$controlToken->tokenString, [$controlToken->headString, $controlToken->payloadString, $controlToken->encodedSignature]],
                        [$tokenWithInvalidType, ['__invalid_token_type_head', $controlToken->payloadString, $controlToken->encodedSignature]],
                        [$tokenWithInvalidAlgorithm, ['__invalid_algorithm_head', $controlToken->payloadString, $controlToken->encodedSignature]],
                    ]],
                    'deserialiaze' => ['__map' => [
                        [$controlToken->headString, $controlToken->head],
                        [$controlToken->payloadString, $controlToken->payload],
                        ['__invalid_token_type_head', $invalidTypeHead],
                        ['__invalid_algorithm_head', $invalidAlgorithmHead],
                    ]],
                    'implode' => $controlToken->tokenString,
                    'decode' => $controlToken->signature,
                    'signature' => $controlToken->signature,
                ]);

                // Create "TokenGenerator" service
                $tokenGenerator = $this->createTokenGeneratorInstance(null, $serializerServiceMock);

                $testCaseBaseError = "Test testFromString, case \"{$tokenType->value}_{$algorithm->value}\" failed: ";

                // Generate token and check generated token params
                $token = $tokenGenerator->fromString($controlToken->tokenString, $tokenType);
                $this->checkTokenParams($token, $controlToken, $tokenType, $testCaseBaseError);

                // Check exceptions
                $testCases = [
                    [
                        $tokenWithInvalidType,
                        new TokenGeneratorException('Invalid token head', TokenGeneratorException::INVALID_TOKEN_TYPE),
                    ],
                    [
                        $tokenWithInvalidAlgorithm,
                        new TokenGeneratorException('Invalid token head', TokenGeneratorException::INVALID_ALGORITHM),
                    ],
                ];
                foreach ($testCases as $testCase) {
                    /** @var Exception $exception */
                    [$invalidToken, $exception] = $testCase;
                    $testCaseBaseError .= sprintf('"%s" exception ', $exception->getMessage());
                    $exceptionWasThrown = false;
                    try {
                        $tokenGenerator->fromString($invalidToken, $tokenType);
                    } catch (Exception $e) {
                        $exceptionWasThrown = true;
                        $this->assertInstanceOf($exception::class, $e, $testCaseBaseError . sprintf('has an incorrect class: %s', $e::class));
                        $this->assertSame($exception->getCode(), $e->getCode(), $testCaseBaseError . sprintf('has an incorrect code: %s', $e->getCode()));
                    }
                    if (!$exceptionWasThrown) {
                        $this->assertEquals(0 ,1, $testCaseBaseError . 'was not thrown');
                    }
                }

            }
        }
    }

    private function checkTokenParams(Token $token, TokenTestPramsEntity $controlToken, TokenType $tokenType, string $testCaseBaseError): void
    {
        $this->assertInstanceOf(Token::class, $token, $testCaseBaseError . 'Token has an incorrect type :' . $token::class);
        $this->assertSame($tokenType, $token->getType(), $testCaseBaseError . "Token has an invalid type: {$token->getType()->value}");
        $this->assertSame($controlToken->tokenString, $token->getToken(), $testCaseBaseError . 'Token has an invalid token');
        $this->assertEquals($controlToken->createdAt, $token->getCreatedAt(), $testCaseBaseError . 'Token has an invalid "createdAt"');
        $this->assertEquals($controlToken->expiredAt, $token->getExpiredAt(), $testCaseBaseError . 'Token has an invalid "expiredAt"');
        $this->assertSame($controlToken->head, $token->getHead(), $testCaseBaseError . 'Token has an invalid head');
        $this->assertSame($controlToken->payload, $token->getPayload(), $testCaseBaseError . 'Token has an invalid payload');
        $this->assertSame($controlToken->signature, $token->getSignature(), $testCaseBaseError . 'Token has an invalid signature');
    }
}