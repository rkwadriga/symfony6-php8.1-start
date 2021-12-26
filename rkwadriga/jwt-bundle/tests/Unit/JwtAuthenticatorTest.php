<?php declare(strict_types=1);
/**
 * Created 2021-12-26
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use Rkwadriga\JwtBundle\Authenticator\JwtAuthenticator;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Exception\SerializerException;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\TokenGenerator;
use Rkwadriga\JwtBundle\Service\TokenIdentifier;
use Rkwadriga\JwtBundle\Service\TokenValidator;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken as AuthenticationToken;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/JwtAuthenticatorTest.php
 */
class JwtAuthenticatorTest extends AbstractUnitTestCase
{
    use UserInstanceTrait;
    use AuthenticationTrait;

    public function testSupports(): void
    {
        // Create authenticator instance
        $authenticator = $this->createJwtAuthenticatorInstance();

        // Check true
        $requestMock = $this->createMock(Request::class, ['get' => null]);
        $this->assertTrue($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => false]);
        $this->assertTrue($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => 'invalid_route_1']);
        $this->assertTrue($authenticator->supports($requestMock));

        // Check false
        $requestMock = $this->createMock(Request::class, ['get' => $this->getConfigDefault(ConfigurationParam::LOGIN_URL)]);
        $this->assertFalse($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => $this->getConfigDefault(ConfigurationParam::REFRESH_URL)]);
        $this->assertFalse($authenticator->supports($requestMock));
    }

    public function testAuthenticate(): void
    {
        // Create request mock
        $requestMock = $this->createMock(Request::class);

        // Ror all hashing algorithms
        foreach (Algorithm::cases() as $algorithm) {
            $userID = $algorithm->value . '_test_user';
            $testCaseBaseError = "Test testAuthenticate case \"{$algorithm->value}\" failed: ";

            // Create user
            $user = $this->createUser($userID);

            // Create access token
            $accessToken = $this->createToken($algorithm, TokenType::ACCESS);

            // Create authenticator instance
            $authenticator = $this->createAuthenticatorService($user, $accessToken);

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
            ];
            foreach ($testCases as $exceptions) {
                [$expected, $previous] = $exceptions;
                $error = $expected->getMessage();
                $subTestCaseBaseError = $testCaseBaseError . "identify ({$error}): ";
                $tokenIdentifier = $this->mockTokenIdentifierService(['identify' => $previous]);
                $authenticator = $this->createAuthenticatorService($user, $accessToken, $tokenIdentifier);

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
                $subTestCaseBaseError = $testCaseBaseError . "tokenGeneration ({$error}): ";
                $tokenGenerator = $this->mockTokenGeneratorService(['fromString' => $previous]);
                $authenticator = $this->createAuthenticatorService($user, $accessToken, null, $tokenGenerator);

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
                    new AuthenticationException('Invalid access token', TokenValidatorException::INVALID_ACCESS_TOKEN),
                    new TokenValidatorException('Invalid access token', TokenValidatorException::INVALID_ACCESS_TOKEN)
                ],
            ];
            foreach ($testCases as $exceptions) {
                [$expected, $previous] = $exceptions;
                $error = $expected->getMessage();
                $subTestCaseBaseError = $testCaseBaseError . "validation ({$error}): ";
                $tokenValidator = $this->mockTokenValidatorService(['validate' => $previous]);
                $authenticator = $this->createAuthenticatorService($user, $accessToken, null, null, $tokenValidator);

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
        }
    }

    public function testOnAuthenticationSuccess(): void
    {
        // Mock request
        $requestMock = $this->createMock(Request::class);

        // Mock authentication token
        $authenticationTokenMock = $this->createMock(AuthenticationToken::class);

        // For all token types...
        foreach (Algorithm::cases() as $algorithm){
            $userID = $algorithm->value . '_test_user';
            // Create user
            $user = $this->createUser($userID);

            // Create access token
            $accessToken = $this->createToken($algorithm, TokenType::ACCESS);

            // Create authenticator instance
            $authenticator = $this->createAuthenticatorService($user, $accessToken);

            // Check successful authentication
            $result = $authenticator->onAuthenticationSuccess($requestMock, $authenticationTokenMock, 'main');
            $this->assertNull($result);
        }
    }

    public function testOnAuthenticationFailure(): void
    {
        /** @var array<Exception> $testCases */
        $testCases = [
            new AuthenticationException('Authentication error'),
        ];

        // Create authenticator instance
        $authenticator = $this->createJwtAuthenticatorInstance();

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
        User|Exception $user,
        Token $token,
        ?TokenIdentifier $identifier = null,
        ?TokenGenerator $tokenGenerator = null,
        ?TokenValidator $tokenValidator = null,
        ?Config $configService = null,
    ): JwtAuthenticator {
        if ($identifier === null) {
            $identifier = $this->mockTokenIdentifierService(['identify' => $token->getToken()]);
        }
        if ($tokenGenerator === null) {
            $tokenGenerator = $this->mockTokenGeneratorService(['fromString' => $token]);
        }
        if ($tokenValidator === null) {
            $tokenValidator = $this->mockTokenValidatorService();
        }
        $userProviderMock = $this->mockUserProvider($user);

        return $this->createJwtAuthenticatorInstance(
            $identifier,
            $tokenGenerator,
            $tokenValidator,
            $configService,
            $userProviderMock
        );
    }
}