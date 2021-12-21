<?php declare(strict_types=1);
/**
 * Created 2021-12-21
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;
use Rkwadriga\JwtBundle\Service\TokenGenerator;

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
                $created = time();
                $userID = $tokenType->value . '_' . $algorithm->value;
                $head = ['alg' => $algorithm->value, 'typ' => 'JWT', 'sub' => $tokenType->value];
                $payload = ['created' => $created, 'email' => $userID];
                [$headString, $payloadString] = [$this->encodeRefreshTokenData($head), $this->encodeRefreshTokenData($payload)];
                [$createdAtDateTime, $expiredAtDateTime] = $this->getRefreshTokenLifeTime($created, $tokenType);
                $contentPart = $this->implodeRefreshTokenParts($headString, $payloadString);
                $signature = $this->getRefreshTokenSignature($algorithm, $head, $payload);
                $encodedSignature = $this->encodeRefreshTokenPart($signature);
                $tokenString = $this->implodeRefreshTokenParts($contentPart, $encodedSignature);

                // Mock services...
                // ... ConfigService mock
                $configServiceMock = $this->mockConfigService([ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value]);
                // ... SerializerService mock
                $serializerServiceMock = $this->mockSerializerService([
                    'serialize' => ['__map' => [[$head, $headString], [$payload, $payloadString]]],
                    'implode' => ['__map' => [[$headString, $payloadString, $contentPart], [$contentPart, $encodedSignature, $tokenString]]],
                    'signature' => $signature,
                    'encode' => ['__map' => [[$signature, $encodedSignature]]],
                ]);
                // ... HeadGeneratorService mock
                $headGeneratorServiceMock = $this->mockHeadGeneratorService(['generate' => $head]);

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
                        $testCaseBaseError = "Test case \"{$tokenType->value}_{$algorithm->value}_{$tokenCreationContext->value}\" failed: ";

                        // Generate token
                        if ($algorithmGivenType === 'mocked_algorithm') {
                            $token = $tokenGenerator->fromPayload($payload, $tokenType, $tokenCreationContext);
                        } else {
                            $token = $tokenGenerator->fromPayload($payload, $tokenType, $tokenCreationContext, $algorithm);
                        }

                        // Check token params
                        $this->assertInstanceOf(Token::class, $token, $testCaseBaseError . 'Token has an incorrect type :' . $token::class);
                        $this->assertSame($tokenType, $token->getType(), $testCaseBaseError . "Token has an invalid type: {$token->getType()->value}");
                        $this->assertSame($tokenString, $token->getToken(), $testCaseBaseError . 'Token has an invalid token');
                        $this->assertEquals($createdAtDateTime, $token->getCreatedAt(), $testCaseBaseError . 'Token has an invalid "createdAt"');
                        $this->assertEquals($expiredAtDateTime, $token->getExpiredAt(), $testCaseBaseError . 'Token has an invalid "expiredAt"');
                        $this->assertSame($head, $token->getHead(), $testCaseBaseError . 'Token has an invalid head');
                        $this->assertSame($payload, $token->getPayload(), $testCaseBaseError . 'Token has an invalid payload');
                        $this->assertSame($signature, $token->getSignature(), $testCaseBaseError . 'Token has an invalid signature');
                    }
                }

            }
        }
    }
}