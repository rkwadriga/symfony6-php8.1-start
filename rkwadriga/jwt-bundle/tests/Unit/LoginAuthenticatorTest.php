<?php declare(strict_types=1);
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use Rkwadriga\JwtBundle\Authenticator\LoginAuthenticator;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;
use Rkwadriga\JwtBundle\Exception\TokenGeneratorException;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken as SystemToken;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/LoginAuthenticatorTest.php
 */
class LoginAuthenticatorTest extends AbstractUnitTestCase
{
    use UserInstanceTrait;
    use AuthenticationTrait;

    public function testSupports(): void
    {
        $authenticator = $this->createLoginAuthenticatorInstance();

        // Check true
        $requestMock = $this->createMock(Request::class, ['get' => $this->getConfigDefault(ConfigurationParam::LOGIN_URL)]);
        $this->assertTrue($authenticator->supports($requestMock));

        // Check false
        $requestMock = $this->createMock(Request::class, ['get' => null]);
        $this->assertFalse($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => false]);
        $this->assertFalse($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => 'invalid_route_1']);
        $this->assertFalse($authenticator->supports($requestMock));

        $requestMock = $this->createMock(Request::class, ['get' => $this->getConfigDefault(ConfigurationParam::REFRESH_URL)]);
        $this->assertFalse($authenticator->supports($requestMock));
    }

    public function testAuthenticate(): void
    {
        $testCaseBaseError = 'Test testAuthenticate failed: ';
        [$loginParam, $passwordParam] = [$this->getConfigDefault(ConfigurationParam::LOGIN_PARAM), $this->getConfigDefault(ConfigurationParam::PASSWORD_PARAM)];

        // Create user
        $user = $this->createUser();

        // Mock request
        $requestMock = $this->createLoginRequestMock($user);

        // Create authenticator instance
        $authenticator = $this->createAuthenticatorService($user);

        // Check successful result
        $result = $authenticator->authenticate($requestMock);
        $this->assertInstanceOf(SelfValidatingPassport::class, $result);
        $this->assertSame($user, $result->getUser());

        // Check "invalid request" exceptions
        $testCases = [
            'not-json body' => 'Invalid request body',
            'missed username field' => [$passwordParam => $user->getPassword()],
            'missed password field' => [$loginParam => $user->getEmail()],
            'empty username field' => [$loginParam => null, $passwordParam => $user->getPassword()],
            'empty password field' => [$loginParam => $user->getEmail(), $passwordParam => null],
        ];
        foreach ($testCases as $errorKey => $requestBody) {
            $exceptionWasThrown = false;
            $concreteTestCaseError = $testCaseBaseError . "\"Invalid request ({$errorKey}\" failed: ";
            $requestMock = $this->createLoginRequestMock($user, $requestBody);
            try {
                $authenticator->authenticate($requestMock);
            } catch (Exception $e) {
                $exceptionWasThrown = true;
                $this->assertInstanceOf(CustomUserMessageAuthenticationException::class, $e, $concreteTestCaseError . 'Exception has an invalid type: ' . $e::class);
            }
            if (!$exceptionWasThrown) {
                $this->assertEquals(0 ,1, $concreteTestCaseError . 'exception was not thrown');
            }
        }

        // Check "User not found" exception
        $exceptionWasThrown = false;
        // Create authenticator instance with exception on trying to identify the user
        $userNotFoundException = new UserNotFoundException(sprintf('User "%s" not found.', $loginParam));
        $authenticator = $this->createAuthenticatorService($user, true, $userNotFoundException);
        $requestMock = $this->createLoginRequestMock($user);
        try {
            $authenticator->authenticate($requestMock);
        } catch (Exception $e) {
            $exceptionWasThrown = true;
            $message = $testCaseBaseError . '"User not found" exception has an invalid type: ' . $e::class;
            $this->assertInstanceOf(UserNotFoundException::class, $e, $message);
        }
        if (!$exceptionWasThrown) {
            $this->assertEquals(0 ,1, $testCaseBaseError . '"User not found" exception was not thrown');
        }

        // Check "invalid password" exception
        $exceptionWasThrown = false;
        $authenticator = $this->createAuthenticatorService($user, false);
        $requestMock = $this->createLoginRequestMock($user);
        try {
            $authenticator->authenticate($requestMock);
        } catch (Exception $e) {
            $exceptionWasThrown = true;
            $message = $testCaseBaseError . '"Invalid password" exception has an invalid type: ' . $e::class;
            $this->assertInstanceOf(BadCredentialsException::class, $e, $message);
        }
        if (!$exceptionWasThrown) {
            $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid password" exception was not thrown');
        }
    }

    public function testOnAuthenticationSuccess(): void
    {
        foreach (Algorithm::cases() as $algorithm) {
            $userID = $algorithm->value . '_test_user';
            $userIdentifier = $this->getConfigDefault(ConfigurationParam::USER_IDENTIFIER);
            // Create tokens and token response
            [$accessToken, $refreshToken] = $this->createTokensPair($algorithm, $userID);
            $tokenResponse = $this->createTokenResponseArray($accessToken, $refreshToken);
            $tokenResponseString = json_encode($tokenResponse);
            $invalidPayloadUserIDEmpty = $invalidPayloadUserIDNull = $accessToken->getPayload();
            unset($invalidPayloadUserIDEmpty[$userIdentifier]);
            $invalidPayloadUserIDNull[$userIdentifier] = null;
            $refreshTokenPayloadUserIDEmptyMock = $this->mockToken($accessToken, ['getPayload' => $invalidPayloadUserIDEmpty]);
            $refreshTokenPayloadUserIDENullMock = $this->mockToken($accessToken, ['getPayload' => $invalidPayloadUserIDNull]);

            // Mock payload generator
            $payloadGeneratorMock = $this->mockPayloadGeneratorService(['generate' => $accessToken->getPayload()]);
            // Mock token generator service
            $tokenGeneratorMock = $this->mockTokenGeneratorService(['fromPayload' => ['__map' => [
                [$accessToken->getPayload(), TokenType::ACCESS, TokenCreationContext::LOGIN, null, $accessToken],
                [$accessToken->getPayload(), TokenType::REFRESH, TokenCreationContext::LOGIN, null, $refreshToken],
                [$invalidPayloadUserIDEmpty, TokenType::ACCESS, TokenCreationContext::LOGIN, null, $refreshTokenPayloadUserIDEmptyMock],
                [$invalidPayloadUserIDNull, TokenType::ACCESS, TokenCreationContext::LOGIN, null, $refreshTokenPayloadUserIDENullMock],
                [$invalidPayloadUserIDEmpty, TokenType::REFRESH, TokenCreationContext::LOGIN, null, $refreshTokenPayloadUserIDEmptyMock],
                [$invalidPayloadUserIDNull, TokenType::REFRESH, TokenCreationContext::LOGIN, null, $refreshTokenPayloadUserIDENullMock],
            ]]]);
            // Mock DbManager service
            $dbManagerMock = $this->mockDbManagerService();
            // Mock ResponseCreator service
            $tokenResponseCreatorMock = $this->mockTokenResponseCreatorService(['create' => $tokenResponse]);
            // Mock Serializer service
            $serializer = $this->mockResponseSerializer(['serialize' => $tokenResponseString]);
            // Mock request
            $requestMock = $this->createMock(Request::class);
            // Mock system token
            $systemToken = $this->createMock(SystemToken::class);

            // Check for both cases - when refresh_token_in_db is enabled and not
            $testCases = ['refresh_token_in_db Enabled' => true, 'refresh_token_in_db Disabled' => false];
            foreach ($testCases as $testCaseName => $isDbServiceEnabled) {
                $testCaseBaseError = "Test testOnAuthenticationSuccess case \"{$testCaseName}\" failed: ";
                // Mock config service
                $configMock = $this->mockConfigService([ConfigurationParam::REFRESH_TOKEN_IN_DB->value => $isDbServiceEnabled]);

                // Create authenticator instance
                $authenticator = $this->createLoginAuthenticatorInstance(
                    null,
                    null,
                    $configMock,
                    $payloadGeneratorMock,
                    $tokenGeneratorMock,
                    $dbManagerMock,
                    $tokenResponseCreatorMock,
                    $serializer
                );

                // Check successful result
                $result = $authenticator->onAuthenticationSuccess($requestMock, $systemToken, 'main');
                $this->assertInstanceOf(JsonResponse::class, $result,
                    $testCaseBaseError . 'Response has an incorrect type: ' . $result::class
                );
                $this->assertSame(Response::HTTP_CREATED, $result->getStatusCode(),
                    $testCaseBaseError . 'Invalid response status code: ' . $result->getStatusCode()
                );
                $this->assertSame($tokenResponseString, $result->getContent(),
                    $testCaseBaseError . 'Invalid response content: ' . $result->getContent()
                );

                // Check "User identifier missed in payload" exception
                // ...for both variants - when user identifier in payload is not set and when it equals to null
                $subTestCases = ['user identifier in payload is not set' => 'empty', 'user identifier in payload is null' => 'null'];
                foreach ($subTestCases as $subTestCaseName => $subTestCaseType) {
                    $subTestCaseBaseError = $testCaseBaseError . "({$subTestCaseName}): ";
                    $invalidPayload = $subTestCaseType === 'empty' ? $invalidPayloadUserIDEmpty : $invalidPayloadUserIDNull;
                    $invalidPayloadGeneratorMock = $this->mockPayloadGeneratorService(['generate' => $invalidPayload]);
                    $authenticator = $this->createLoginAuthenticatorInstance(
                        null,
                        null,
                        $configMock,
                        $invalidPayloadGeneratorMock,
                        $tokenGeneratorMock,
                        $dbManagerMock,
                        $tokenResponseCreatorMock,
                        $serializer
                    );

                    $exceptionWasThrown = false;
                    try {
                        $authenticator->onAuthenticationSuccess($requestMock, $systemToken, 'main');
                    } catch (Exception $e) {
                        $exceptionWasThrown = true;
                        $this->assertInstanceOf(TokenGeneratorException::class, $e,
                            $subTestCaseBaseError . '"User identifier missed in payload" exception has an incorrect type: ' . $e::class
                        );
                        $this->assertSame(TokenGeneratorException::INVALID_PAYLOAD, $e->getCode(),
                            $subTestCaseBaseError . '"User identifier missed in payload" exception has an incorrect code: ' . $e->getCode()
                        );
                    }
                    if ($isDbServiceEnabled && !$exceptionWasThrown) {
                        $this->assertEquals(0 ,1, $subTestCaseBaseError . '"User identifier missed in payload" exception was not thrown');
                    }
                }
            }
        }
    }

    public function testOnAuthenticationFailure(): void
    {
        $testCases = [
            CustomUserMessageAuthenticationException::class => ['Test message 1', 0, []],
            UserNotFoundException::class => ['Test message 2', 0, null],
            BadCredentialsException::class => ['Test message 3', 0, null],
        ];

        // Create authenticator instance
        $authenticator = $this->createLoginAuthenticatorInstance();

        // Create request mock
        $requestMock = $this->createMock(Request::class);

        foreach ($testCases as $exceptionClass => $data) {
            [$message, $code, $messageData] = $data;
            $testCaseBaseError = "Test testOnAuthenticationFailure case \"{$exceptionClass}:{$code}\" failed: ";
            $content = json_encode(['code' => $code, 'message' => $message]);

            if ($messageData !== null) {
                $exception = new $exceptionClass($message, $messageData, $code);
            } else {
                $exception = new $exceptionClass($message, $code);
            }

            $result = $authenticator->onAuthenticationFailure($requestMock, $exception);
            $this->assertInstanceOf(JsonResponse::class, $result,
                $testCaseBaseError . 'Response has an incorrect type: ' . $result::class
            );
            $this->assertSame(Response::HTTP_FORBIDDEN, $result->getStatusCode(),
                $testCaseBaseError . 'Invalid response status code: ' . $result->getStatusCode()
            );
            $this->assertSame($content, $result->getContent(),
                $testCaseBaseError . 'Invalid response content: ' . $result->getContent()
            );
        }
    }

    private function createLoginRequestMock(User $user, array|string $bodyParams = []): Request
    {
        if (is_string($bodyParams)) {
            $body = $bodyParams;
        } else {
            if (empty($bodyParams)) {
                [$loginParam, $passwordParam] = [$this->getConfigDefault(ConfigurationParam::LOGIN_PARAM), $this->getConfigDefault(ConfigurationParam::PASSWORD_PARAM)];
                $bodyParams = [$loginParam => $user->getEmail(), $passwordParam => $user->getPassword()];
            }
            $body = json_encode($bodyParams);
        }

        return $this->createMock(Request::class, ['getContent' => $body]);
    }

    private function createAuthenticatorService(User $user, bool $passwordVerifyingResult = true, ?Exception $exception = null): LoginAuthenticator
    {
        // Mock password hasher and UserProvider
        $hasherFactoryMock = $this->mockPasswordHasherFactory($user->getPassword(), $passwordVerifyingResult);
        $userProviderMock = $this->mockUserProvider($exception ?? $user);

        return $this->createLoginAuthenticatorInstance($userProviderMock, $hasherFactoryMock);
    }
}