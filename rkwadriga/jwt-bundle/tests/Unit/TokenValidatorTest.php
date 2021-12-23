<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use DateTime;
use DateTimeImmutable;
use DateInterval;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/TokenValidatorTest.php
 */
class TokenValidatorTest extends AbstractUnitTestCase
{
    public function AtestValidate(): void
    {
        $tokenValidatorService = $this->createTokenValidatorInstance();

        // For all token types...
        foreach (TokenType::cases() as $tokenType) {
            // For all encoding algorithms...
            foreach (Algorithm::cases() as $algorithm) {
                $created = time();
                $userID = $algorithm->value . '_' . $tokenType->value;
                $testCaseBaseError = "Test testValidate \"{$algorithm->value}_{$tokenType->value}\" case failed: ";
                $invalidTokenType = $tokenType === TokenType::ACCESS ? TokenType::REFRESH : TokenType::ACCESS;

                // Create control token
                $controlToken = $this->createToken($algorithm, $tokenType, $userID, $created);

                // Create token mock
                $tokenMethodsMock = $this->createTokenMethodsMock($controlToken);
                $tokenMock = $this->createMock(Token::class, $tokenMethodsMock);

                // Check successful validation
                $tokenValidatorService->validate($tokenMock, $tokenType);

                // Check "Token expired" exception
                $exceptionWasThrown = false;
                $lifeTime = $this->getConfigDefault($tokenType === TokenType::ACCESS ? ConfigurationParam::ACCESS_TOKEN_LIFE_TIME : ConfigurationParam::REFRESH_TOKEN_LIFE_TIME);
                $invalidExpiredAt = DateTime::createFromInterface($controlToken->getExpiredAt());
                $invalidExpiredAt->add(DateInterval::createFromDateString("-{$lifeTime} seconds"));
                $invalidTokenMock = $this->createMock(Token::class, array_merge($tokenMethodsMock, ['getExpiredAt' => DateTimeImmutable::createFromMutable($invalidExpiredAt)]));
                try {
                    $tokenValidatorService->validate($invalidTokenMock, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasThrown = true;
                    $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::ACCESS_TOKEN_EXPIRED : TokenValidatorException::REFRESH_TOKEN_EXPIRED;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame($code, $e->getCode());
                }
                if (!$exceptionWasThrown) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Token expired" exception was not thrown');
                }

                // Check "Invalid token type" exception
                $exceptionWasThrown = false;
                $invalidTokenMock = $this->createMock(Token::class, array_merge($tokenMethodsMock, ['getType' => $invalidTokenType]));
                try {
                    $tokenValidatorService->validate($invalidTokenMock, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasThrown = true;
                    $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame($code, $e->getCode());
                }
                if (!$exceptionWasThrown) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid token type" exception was not thrown');
                }

                // Check "Invalid token payload" exception
                $exceptionWasThrown = false;
                $invalidPayload = $controlToken->getPayload();
                unset($invalidPayload[$this->getConfigDefault(ConfigurationParam::USER_IDENTIFIER)]);
                $invalidTokenMock = $this->createMock(Token::class, array_merge($tokenMethodsMock, ['getPayload' => $invalidPayload]));
                try {
                    $tokenValidatorService->validate($invalidTokenMock, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasThrown = true;
                    $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame($code, $e->getCode());
                }
                if (!$exceptionWasThrown) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid token payload" exception was not thrown');
                }

                // Check "Invalid token head (token type not set)" exception
                $exceptionWasThrown = false;
                $invalidHead = $controlToken->getHead();
                unset($invalidHead['sub']);
                $invalidTokenMock = $this->createMock(Token::class, array_merge($tokenMethodsMock, ['getHead' => $invalidHead]));
                try {
                    $tokenValidatorService->validate($invalidTokenMock, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasThrown = true;
                    $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame($code, $e->getCode());
                }
                if (!$exceptionWasThrown) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid token head (token type not set)" exception was not thrown');
                }

                // Check "Invalid token head (token type is null)" exception
                $exceptionWasThrown = false;
                $invalidHead = array_merge($controlToken->getHead(), ['sub' => null]);
                $invalidTokenMock = $this->createMock(Token::class, array_merge($tokenMethodsMock, ['getHead' => $invalidHead]));
                try {
                    $tokenValidatorService->validate($invalidTokenMock, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasThrown = true;
                    $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame($code, $e->getCode());
                }
                if (!$exceptionWasThrown) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid token head (token type is null)" exception was not thrown');
                }

                // Check "Invalid token head (invalid token type)" exception
                $exceptionWasThrown = false;
                $invalidHead = array_merge($controlToken->getHead(), ['sub' => $invalidTokenType->value]);
                $invalidTokenMock = $this->createMock(Token::class, array_merge($tokenMethodsMock, ['getHead' => $invalidHead]));
                try {
                    $tokenValidatorService->validate($invalidTokenMock, $tokenType);
                } catch (Exception $e) {
                    $exceptionWasThrown = true;
                    $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
                    $this->assertInstanceOf(TokenValidatorException::class, $e);
                    $this->assertSame($code, $e->getCode());
                }
                if (!$exceptionWasThrown) {
                    $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid token head (invalid token type)" exception was not thrown');
                }
            }
        }
    }

    public function testValidateRefresh(): void
    {
        $tokenValidatorService = $this->createTokenValidatorInstance();

        // For all encoding algorithms...
        foreach (Algorithm::cases() as $algorithm) {
            $created = time();
            $userID = $algorithm->value;
            $testCaseBaseError = "Test testValidateRefresh \"{$algorithm->value}\" case failed: ";
            $userIdentifier = $this->getConfigDefault(ConfigurationParam::USER_IDENTIFIER);

            // Create control tokens
            [$controlAccessToken, $controlRefreshToken] = $this->createTokensPair($algorithm, $userID, $created);

            // Create tokens mock
            $accessTokenMethodsMock = $this->createTokenMethodsMock($controlAccessToken);
            $accessTokenMock = $this->createMock(Token::class, $accessTokenMethodsMock);
            $refreshTokenMethodsMock = $this->createTokenMethodsMock($controlRefreshToken);
            $refreshTokenMock = $this->createMock(Token::class, $refreshTokenMethodsMock);

            // Check successful validation
            $tokenValidatorService->validateRefresh($refreshTokenMock, $accessTokenMock);

            // Check "Invalid refresh token payload (userID not set)" exception
            $exceptionWasThrown = false;
            $invalidPayload = $controlRefreshToken->getPayload();
            unset($invalidPayload[$userIdentifier]);
            $invalidRefreshTokenMethodsMock = $this->createMock(Token::class, array_merge($refreshTokenMethodsMock, ['getPayload' => $invalidPayload]));
            try {
                $tokenValidatorService->validateRefresh($invalidRefreshTokenMethodsMock, $accessTokenMock);
            } catch (Exception $e) {
                $exceptionWasThrown = true;
                $this->assertInstanceOf(TokenValidatorException::class, $e);
                $this->assertSame(TokenValidatorException::INVALID_REFRESH_TOKEN, $e->getCode());
            }
            if (!$exceptionWasThrown) {
                $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid refresh token payload (userID not set)" exception was not thrown');
            }

            // Check "Invalid refresh token payload (userID is null)" exception
            $exceptionWasThrown = false;
            $invalidPayload = array_merge($controlRefreshToken->getPayload(), [$userIdentifier => null]);
            $invalidRefreshTokenMethodsMock = $this->createMock(Token::class, array_merge($refreshTokenMethodsMock, ['getPayload' => $invalidPayload]));
            try {
                $tokenValidatorService->validateRefresh($invalidRefreshTokenMethodsMock, $accessTokenMock);
            } catch (Exception $e) {
                $exceptionWasThrown = true;
                $this->assertInstanceOf(TokenValidatorException::class, $e);
                $this->assertSame(TokenValidatorException::INVALID_REFRESH_TOKEN, $e->getCode());
            }
            if (!$exceptionWasThrown) {
                $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid refresh token payload (userID is null)" exception was not thrown');
            }

            // Check "Invalid refresh token payload (userID is invalid)" exception
            $exceptionWasThrown = false;
            $invalidPayload = array_merge($controlRefreshToken->getPayload(), [$userIdentifier => $userID . '_1']);
            $invalidRefreshTokenMethodsMock = $this->createMock(Token::class, array_merge($refreshTokenMethodsMock, ['getPayload' => $invalidPayload]));
            try {
                $tokenValidatorService->validateRefresh($invalidRefreshTokenMethodsMock, $accessTokenMock);
            } catch (Exception $e) {
                $exceptionWasThrown = true;
                $this->assertInstanceOf(TokenValidatorException::class, $e);
                $this->assertSame(TokenValidatorException::INVALID_REFRESH_TOKEN, $e->getCode());
            }
            if (!$exceptionWasThrown) {
                $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid refresh token payload (userID is invalid)" exception was not thrown');
            }

            // Check "Invalid refresh token payload ("created" is invalid)" exception
            $exceptionWasThrown = false;
            $invalidPayload = array_merge($controlRefreshToken->getPayload(), ['created' => $created + 1]);
            $invalidRefreshTokenMethodsMock = $this->createMock(Token::class, array_merge($refreshTokenMethodsMock, ['getPayload' => $invalidPayload]));
            try {
                $tokenValidatorService->validateRefresh($invalidRefreshTokenMethodsMock, $accessTokenMock);
            } catch (Exception $e) {
                $exceptionWasThrown = true;
                $this->assertInstanceOf(TokenValidatorException::class, $e);
                $this->assertSame(TokenValidatorException::INVALID_REFRESH_TOKEN, $e->getCode());
            }
            if (!$exceptionWasThrown) {
                $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid refresh token payload ("created" is invalid)" exception was not thrown');
            }
        }
    }

    private function createTokenMethodsMock(Token $token): array
    {
        return [
            'getType' => $token->getType(),
            'getToken' => $token->getToken(),
            'getCreatedAt' => $token->getCreatedAt(),
            'getExpiredAt' => $token->getExpiredAt(),
            'getHead' => $token->getHead(),
            'getPayload' => $token->getPayload(),
            'getSignature' => $token->getSignature(),
        ];
    }
}