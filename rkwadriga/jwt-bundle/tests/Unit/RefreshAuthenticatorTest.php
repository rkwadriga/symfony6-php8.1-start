<?php declare(strict_types=1);
/**
 * Created 2021-12-24
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use Rkwadriga\JwtBundle\Authenticator\RefreshAuthenticator;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\RefreshToken256;
use Rkwadriga\JwtBundle\Entity\RefreshToken512;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;
use Rkwadriga\JwtBundle\Exception\DbServiceException;
use Rkwadriga\JwtBundle\Exception\SerializerException;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\DbManager;
use Rkwadriga\JwtBundle\Service\TokenGenerator;
use Rkwadriga\JwtBundle\Service\TokenIdentifier;
use Rkwadriga\JwtBundle\Service\TokenValidator;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken as AuthenticationToken;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/RefreshAuthenticatorTest.php
 */
class RefreshAuthenticatorTest extends AbstractUnitTestCase
{
    use UserInstanceTrait;
    use AuthenticationTrait;

    public function testSupports(): void
    {
        $authenticator = $this->createRefreshAuthenticatorInstance();

        // Check true
        $requestMock = $this->createMock(Request::class, ['get' => $this->getConfigDefault(ConfigurationParam::REFRESH_URL)]);
        $this->assertTrue($authenticator->supports($requestMock));

        // Check false
        $requestMock = $this->createMock(Request::class, ['get' => null]);
        $this->assertFalse($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => false]);
        $this->assertFalse($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => 'invalid_route_1']);
        $this->assertFalse($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => $this->getConfigDefault(ConfigurationParam::LOGIN_URL)]);
        $this->assertFalse($authenticator->supports($requestMock));
    }

    public function testAuthenticate(): void
    {
        // Mock request
        $requestMock = $this->createMock(Request::class);

        // For all token types...
        foreach (Algorithm::cases() as $algorithm) {
            $time = time();
            $userID = $algorithm->value . '_test_user';
            $testCaseStartError = "Test testAuthenticate case \"{$algorithm->value}\" ";

            // Create user
            $user = $this->createUser($userID);

            // Create token pair
            [$accessToken, $refreshToken] = $this->createTokensPair($algorithm, $userID, $time);

            // Check for both cases - when refresh_token_in_db is enabled and not
            $testCases = ['refresh_token_in_db Enabled' => true, 'refresh_token_in_db Disabled' => false];
            foreach ($testCases as $testCaseName => $isDbServiceEnabled) {
                $testCaseBaseError = $testCaseStartError . "\"{$testCaseName}\" failed: ";

                // Mock "Config" service
                $configMock = $this->mockConfigService([ConfigurationParam::REFRESH_TOKEN_IN_DB->value => $isDbServiceEnabled]);

                // Create authenticator instance
                $authenticator = $this->createAuthenticatorService(
                    $algorithm,
                    $user,
                    $accessToken,
                    $refreshToken,
                    $configMock,
                    $requestMock
                );

                // Check successful authentication
                $result = $authenticator->authenticate($requestMock);
                $this->assertInstanceOf(SelfValidatingPassport::class, $result);
                $this->assertSame($user, $result->getUser());

                // Check token identifier exceptions
                $testCases = [
                    [
                        new AuthenticationException('Invalid access token type', TokenIdentifierException::INVALID_ACCESS_TOKEN),
                        new TokenIdentifierException('Invalid access token type', TokenIdentifierException::INVALID_ACCESS_TOKEN)
                    ],
                    [
                        new AuthenticationException('Access token missed', TokenIdentifierException::ACCESS_TOKEN_MISSED),
                        new TokenIdentifierException('Access token missed', TokenIdentifierException::ACCESS_TOKEN_MISSED)
                    ],
                    [
                        new AuthenticationException('Refresh token missed', TokenIdentifierException::REFRESH_TOKEN_MISSED),
                        new TokenIdentifierException('Refresh token missed', TokenIdentifierException::REFRESH_TOKEN_MISSED)
                    ],
                ];
                foreach ($testCases as $exceptions) {
                    [$expected, $previous] = $exceptions;
                    $error = $expected->getMessage();
                    $subTestCaseBaseError = $testCaseBaseError . "identify ({$error}): ";
                    $tokenIdentifier = $this->mockTokenIdentifierService(['identify' => $previous]);
                    $authenticator = $this->createAuthenticatorService(
                        $algorithm,
                        $user,
                        $accessToken,
                        $refreshToken,
                        $configMock,
                        $requestMock,
                        $tokenIdentifier,
                    );
                    $exceptionWasThrown = false;
                    try {
                        $authenticator->authenticate($requestMock);
                    } catch (\Exception $e) {
                        $exceptionWasThrown = true;
                        $this->compareExceptions($subTestCaseBaseError, $e, $expected, $previous);
                    }
                    if (!$exceptionWasThrown) {
                        $this->assertEquals(0 ,1, $testCaseBaseError . "\"{$error}\" exception was not thrown");
                    }
                }

                // Check token generator exceptions
                /** @var array<Exception> $testCases */
                $testCases = [
                    [
                        new AuthenticationException('Invalid token format', TokenValidatorException::INVALID_FORMAT),
                        new TokenValidatorException('Invalid token format', TokenValidatorException::INVALID_FORMAT)
                    ],
                    [
                        new AuthenticationException('Invalid token json', SerializerException::INVALID_JSON_DATA),
                        new SerializerException('Invalid token json', SerializerException::INVALID_JSON_DATA)
                    ],
                    [
                        new AuthenticationException('Invalid token header ("sub" param)', TokenValidatorException::INVALID_TYPE),
                        new TokenValidatorException('Invalid token header ("sub" param)', TokenValidatorException::INVALID_TYPE)
                    ],
                    [
                        new AuthenticationException('Invalid token signature', TokenValidatorException::INVALID_SIGNATURE),
                        new TokenValidatorException('Invalid token signature', TokenValidatorException::INVALID_SIGNATURE)
                    ],
                    [
                        new AuthenticationException('Invalid base64 data', SerializerException::INVALID_BASE64_DATA),
                        new SerializerException('Invalid base64 data', SerializerException::INVALID_BASE64_DATA)
                    ],
                ];
                foreach ($testCases as $exceptions) {
                    [$expected, $previous] = $exceptions;
                    $error = $expected->getMessage();
                    $subTestCaseBaseError = $testCaseBaseError . "generate ({$error}): ";
                    $tokenGenerator = $this->mockTokenGeneratorService(['fromString' => $previous]);
                    $authenticator = $this->createAuthenticatorService(
                        $algorithm,
                        $user,
                        $accessToken,
                        $refreshToken,
                        $configMock,
                        $requestMock,
                        null,
                        $tokenGenerator
                    );
                    $exceptionWasThrown = false;
                    try {
                        $authenticator->authenticate($requestMock);
                    } catch (\Exception $e) {
                        $exceptionWasThrown = true;
                        $this->compareExceptions($subTestCaseBaseError, $e, $expected, $previous);
                    }
                    if (!$exceptionWasThrown) {
                        $this->assertEquals(0 ,1, $testCaseBaseError . "\"{$error}\" exception was not thrown");
                    }
                }

                // Check token validator exceptions
                $testCases = [
                    [
                        new AuthenticationException('Access token expired', TokenValidatorException::ACCESS_TOKEN_EXPIRED),
                        new TokenValidatorException('Access token expired', TokenValidatorException::ACCESS_TOKEN_EXPIRED)
                    ],
                    [
                        new AuthenticationException('Refresh token expired', TokenValidatorException::REFRESH_TOKEN_EXPIRED),
                        new TokenValidatorException('Refresh token expired', TokenValidatorException::REFRESH_TOKEN_EXPIRED)
                    ],
                    [
                        new AuthenticationException('Invalid access token', TokenValidatorException::INVALID_ACCESS_TOKEN),
                        new TokenValidatorException('Invalid access token', TokenValidatorException::INVALID_ACCESS_TOKEN)
                    ],
                    [
                        new AuthenticationException('Invalid refresh token', TokenValidatorException::INVALID_REFRESH_TOKEN),
                        new TokenValidatorException('Invalid refresh token', TokenValidatorException::INVALID_REFRESH_TOKEN)
                    ],
                ];
                foreach ($testCases as $exceptions) {
                    [$expected, $previous] = $exceptions;
                    $error = $expected->getMessage();
                    $subTestCaseBaseError = $testCaseBaseError . "validate ({$error}): ";
                    $tokenValidator = $this->mockTokenValidatorService(['validate' => $previous]);
                    $authenticator = $this->createAuthenticatorService(
                        $algorithm,
                        $user,
                        $accessToken,
                        $refreshToken,
                        $configMock,
                        $requestMock,
                        null,
                        null,
                        $tokenValidator,
                    );
                    $exceptionWasThrown = false;
                    try {
                        $authenticator->authenticate($requestMock);
                    } catch (\Exception $e) {
                        $exceptionWasThrown = true;
                        $this->compareExceptions($subTestCaseBaseError, $e, $expected, $previous);
                    }
                    if (!$exceptionWasThrown) {
                        $this->assertEquals(0 ,1, $testCaseBaseError . "\"{$error}\" exception was not thrown");
                    }
                }

                // Check "Validate refresh" exceptions
                $testCases = [
                    [
                        new AuthenticationException('Access token expired', TokenValidatorException::ACCESS_TOKEN_EXPIRED),
                        new TokenValidatorException('Access token expired', TokenValidatorException::ACCESS_TOKEN_EXPIRED)
                    ],
                ];
                foreach ($testCases as $exceptions) {
                    [$expected, $previous] = $exceptions;
                    $error = $expected->getMessage();
                    $subTestCaseBaseError = $testCaseBaseError . "Validate refresh ({$error}): ";
                    $tokenValidator = $this->mockTokenValidatorService(['validateRefresh' => $previous]);
                    $authenticator = $this->createAuthenticatorService(
                        $algorithm,
                        $user,
                        $accessToken,
                        $refreshToken,
                        $configMock,
                        $requestMock,
                        null,
                        null,
                        $tokenValidator,
                    );
                    $exceptionWasThrown = false;
                    try {
                        $authenticator->authenticate($requestMock);
                    } catch (\Exception $e) {
                        $exceptionWasThrown = true;
                        $this->compareExceptions($subTestCaseBaseError, $e, $expected, $previous);
                    }
                    if (!$exceptionWasThrown) {
                        $this->assertEquals(0 ,1, $testCaseBaseError . "\"{$error}\" exception was not thrown");
                    }
                }

                // Check "DbManager" service exceptions
                $testCaseError = $subTestCaseBaseError . '"Refresh token not found in DB"';

                // Mock "DbManager" service
                $dbManagerMock = $this->mockDbManagerService(['findRefreshToken' => null]);
                $authenticator = $this->createAuthenticatorService(
                    $algorithm,
                    $user,
                    $accessToken,
                    $refreshToken,
                    $configMock,
                    $requestMock,
                    null,
                    null,
                    null,
                    $dbManagerMock
                );

                $exceptionWasThrown = false;
                try {
                    $authenticator->authenticate($requestMock);
                } catch (Exception $e) {
                    $exceptionWasThrown = true;
                    $expectedPreviousException = new TokenValidatorException('Refresh token does not exist', TokenValidatorException::INVALID_REFRESH_TOKEN);
                    $expectedException = new AuthenticationException($expectedPreviousException->getMessage(), $expectedPreviousException->getCode());
                    $this->compareExceptions($testCaseError, $e, $expectedException, $expectedPreviousException);
                }
                if ($isDbServiceEnabled && !$exceptionWasThrown) {
                    $this->assertEquals(0 ,1, $testCaseError . 'exception was not thrown');
                }
            }
        }
    }

    public function testOnAuthenticationSuccess(): void
    {
        // Mock request
        $requestMock = $this->createMock(Request::class);

        // Mock authentication token
        $authenticationTokenMock = $this->createMock(AuthenticationToken::class);

        // For all token types...
        foreach (Algorithm::cases() as $algorithm) {
            $time = time();
            $userID = $algorithm->value . '_test_user';
            $testCaseBaseError = "Test testAuthenticate case \"{$algorithm->value}\" ";

            // Create user
            $user = $this->createUser($userID);

            // Create token pair and token response
            [$accessToken, $refreshToken] = $this->createTokensPair($algorithm, $userID, $time);
            $tokenResponse = $this->createTokenResponseArray($accessToken, $refreshToken);
            $tokenResponseString = json_encode($tokenResponse);

            // Check for both cases - when refresh_token_in_db is enabled and not
            $testCases = ['refresh_token_in_db Enabled' => true, 'refresh_token_in_db Disabled' => false];
            foreach ($testCases as $testCaseName => $isDbServiceEnabled) {
                $subTestCaseBaseError = $testCaseBaseError . "\"{$testCaseName}\" failed: ";
                // Mock "Config" service
                $configMock = $this->mockConfigService([ConfigurationParam::REFRESH_TOKEN_IN_DB->value => $isDbServiceEnabled]);

                // Create authenticator instance
                $authenticator = $this->createAuthenticatorService(
                    $algorithm,
                    $user,
                    $accessToken,
                    $refreshToken,
                    $configMock,
                    $requestMock
                );

                // Check successful authentication
                $result = $authenticator->onAuthenticationSuccess($requestMock, $authenticationTokenMock, 'main');
                $this->assertInstanceOf(JsonResponse::class, $result);
                $this->assertSame($tokenResponseString, $result->getContent());

                // Check "DbManager" service exceptions
                /** @var array<DbServiceException> $testCases */
                $testCases = [
                    new DbServiceException('Refresh token not found', DbServiceException::REFRESH_TOKEN_MISSED),
                    new DbServiceException('Can not update refresh token', DbServiceException::SQL_ERROR),
                ];
                foreach ($testCases as $exception) {
                    $testCaseError = $subTestCaseBaseError . $exception->getMessage() . ' ';

                    // Mock "DbManager" service
                    $dbManagerMock = $this->mockDbManagerService(['updateRefreshToken' => $exception]);
                    $authenticator = $this->createAuthenticatorService(
                        $algorithm,
                        $user,
                        $accessToken,
                        $refreshToken,
                        $configMock,
                        $requestMock,
                        null,
                        null,
                        null,
                        $dbManagerMock
                    );

                    $exceptionWasThrown = false;
                    try {
                        $authenticator->onAuthenticationSuccess($requestMock, $authenticationTokenMock, 'main');
                    } catch (Exception $e) {
                        $exceptionWasThrown = true;
                        $this->compareExceptions($testCaseError, $e, $exception);
                    }
                    if ($isDbServiceEnabled && !$exceptionWasThrown) {
                        $this->assertEquals(0 ,1, $testCaseError . 'exception was not thrown');
                    }
                }
            }
        }
    }

    public function testOnAuthenticationFailure(): void
    {
        /** @var array<Exception> $testCases */
        $testCases = [
            new AuthenticationException('Authentication error'),
            new AuthenticationException('Refresh token does not exist', TokenValidatorException::INVALID_REFRESH_TOKEN,
                new TokenValidatorException('Refresh token does not exist', TokenValidatorException::INVALID_REFRESH_TOKEN)
            ),
        ];

        // Create authenticator instance
        $authenticator = $this->createRefreshAuthenticatorInstance();

        // Create request mock
        $requestMock = $this->createMock(Request::class);

        foreach ($testCases as $exception) {
            [$exceptionClass, $message, $code] = [$exception::class, $exception->getMessage(), $exception->getCode()];
            $responseStatusCode = $exception->getCode() === TokenValidatorException::ACCESS_TOKEN_EXPIRED ? Response::HTTP_UNAUTHORIZED : Response::HTTP_FORBIDDEN;
            $testCaseBaseError = "Test testOnAuthenticationFailure case \"{$exceptionClass}:{$code}\" failed: ";
            $content = json_encode(['code' => $code, 'message' => $message]);

            $result = $authenticator->onAuthenticationFailure($requestMock, $exception);
            $this->assertInstanceOf(JsonResponse::class, $result,
                $testCaseBaseError . 'Response has an incorrect type: ' . $result::class
            );
            $this->assertSame($responseStatusCode, $result->getStatusCode(),
                $testCaseBaseError . 'Invalid response status code: ' . $result->getStatusCode()
            );
            $this->assertSame($content, $result->getContent(),
                $testCaseBaseError . 'Invalid response content: ' . $result->getContent()
            );
        }
    }

    private function createAuthenticatorService(
        Algorithm $algorithm,
        User $user,
        Token $accessToken,
        Token $refreshToken,
        ?Config $config = null,
        ?Request $request = null,
        ?TokenIdentifier $tokenIdentifier = null,
        ?TokenGenerator $tokenGenerator = null,
        ?TokenValidator $tokenValidator = null,
        ?DbManager $dbManager = null,
    ): RefreshAuthenticator {
        // Mock Request
        if ($request === null) {
            $request = $this->createMock(Request::class);
        }

        // Mock "Config" service
        if ($config === null) {
            $config = $this->mockConfigService();
        }

        // Mock "DbManager" service
        if ($dbManager === null) {
            $dbManager = $this->mockDbManagerService([
                'findRefreshToken' => $this->createMock($this->getRefreshTokenEntityClass($algorithm)),
            ]);
        }

        // Mock UserProvider
        $userProviderMock = $this->mockUserProvider($user);

        // Mock "TokenIdentifier" service
        if ($tokenIdentifier === null) {
            $tokenIdentifier = $this->mockTokenIdentifierService(['identify' => ['__map' => [
                [$request, TokenType::ACCESS, $accessToken->getToken()],
                [$request, TokenType::REFRESH, $refreshToken->getToken()],
            ]]]);
        }

        // Mock "TokenGenerator" service
        if ($tokenGenerator === null) {
            $tokenGenerator = $this->mockTokenGeneratorService([
                'fromString' => ['__map' => [
                    [$accessToken->getToken(), TokenType::ACCESS, $accessToken],
                    [$refreshToken->getToken(), TokenType::REFRESH, $refreshToken],
                ]],
                'fromPayload' => ['__map' => [
                    [$accessToken->getPayload(), $accessToken->getType(), TokenCreationContext::REFRESH, null, $accessToken],
                    [$accessToken->getPayload(), $refreshToken->getType(), TokenCreationContext::REFRESH, null, $refreshToken],
                ]],
            ]);
        }

        // Mock "TokenValidator" service
        if ($tokenValidator === null) {
            $tokenValidator = $this->mockTokenValidatorService();
        }

        // Mock "ResponseSerializer" service
        $tokenResponse = $this->createTokenResponseArray($accessToken, $refreshToken);
        $responseSerializer = $this->mockResponseSerializer(['serialize' => json_encode($tokenResponse)]);

        // Mock "PayloadGenerator" service
        $payloadGeneratorMock = $this->mockPayloadGeneratorService(['generate' => $accessToken->getPayload()]);

        // Mock "TokenResponseCreator" service
        $tokenResponseCreatorMock = $this->mockTokenResponseCreatorService(['create' => $tokenResponse]);

        // Create authenticator instance
        $authenticator = $this->createRefreshAuthenticatorInstance(
            $userProviderMock,
            $tokenIdentifier,
            $tokenGenerator,
            $tokenValidator,
            $config,
            $dbManager,
            $payloadGeneratorMock,
            $tokenResponseCreatorMock,
            $responseSerializer,
            $refreshToken,
            $user->getEmail()
        );

        return $authenticator;
    }

    private function getRefreshTokenEntityClass(Algorithm $algorithm): string
    {
        return $algorithm === Algorithm::SHA256 ? RefreshToken256::class : RefreshToken512::class;
    }
}