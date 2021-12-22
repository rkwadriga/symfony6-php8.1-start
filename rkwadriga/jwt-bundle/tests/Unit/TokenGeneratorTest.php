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
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
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
                $invalidHead = array_merge($controlToken->head, ['sub' => $tokenType === TokenType::ACCESS ? TokenType::REFRESH->value : TokenType::ACCESS->value]);
                $invalidSignature = $this->getRefreshTokenSignature($algorithm, $invalidHead, $controlToken->payload);
                $tokenWithInvalidHead = $this->implodeRefreshTokenParts('__invalid_head', $controlToken->payloadString, $controlToken->encodedSignature);
                $tokenWithInvalidSignature = $this->implodeRefreshTokenParts($controlToken->headString, $controlToken->payloadString, '__invalid_signature');

                // ... SerializerService mock
                $serializerServiceMock = $this->mockSerializerService([
                    'explode' => ['__map' => [
                        [$controlToken->tokenString, [$controlToken->headString, $controlToken->payloadString, $controlToken->encodedSignature]],
                        [$tokenWithInvalidHead, ['__invalid_head', $controlToken->payloadString, $controlToken->encodedSignature]],
                        [$tokenWithInvalidSignature, [$controlToken->headString, $controlToken->payloadString, '__invalid_signature']],
                    ]],
                    'deserialiaze' => ['__map' => [
                        [$controlToken->headString, $controlToken->head],
                        [$controlToken->payloadString, $controlToken->payload],
                        ['__invalid_head', $invalidHead],
                    ]],
                    'implode' => $controlToken->contentPart,
                    'decode' => ['__map' => [
                        [$controlToken->encodedSignature, $controlToken->signature],
                        ['__invalid_signature', $invalidSignature],
                    ]],
                    'signature' => $controlToken->signature,
                ]);

                // Create "TokenGenerator" service
                $tokenGenerator = $this->createTokenGeneratorInstance(null, $serializerServiceMock);

                $testCaseBaseError = "Test testFromString, case \"{$tokenType->value}_{$algorithm->value}\" failed: ";

                // Generate token
                $token = $tokenGenerator->fromString($controlToken->tokenString, $tokenType);

                // Check token params
                $this->checkTokenParams($token, $controlToken, $tokenType, $testCaseBaseError);

                // Test "Invalid token type" exception
                $exceptionWasTrow = false;
                try {
                    $tokenGenerator->fromString($tokenWithInvalidHead, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasTrow = true;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame(TokenValidatorException::INVALID_TYPE, $e->getCode());
                }
                if (!$exceptionWasTrow) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid token type" exception was not thrown');
                }

                // Test "Invalid signature" exception
                $exceptionWasTrow = false;
                try {
                    $tokenGenerator->fromString($tokenWithInvalidSignature, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasTrow = true;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame(TokenValidatorException::INVALID_SIGNATURE, $e->getCode());
                }
                if (!$exceptionWasTrow) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid signature" exception was not thrown');
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