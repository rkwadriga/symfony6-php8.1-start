<?php declare(strict_types=1);
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

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
            $exceptionWasTrow = false;
            $concreteTestCaseError = $testCaseBaseError . "\"Invalid request ({$errorKey}\" failed: ";
            $requestMock = $this->createLoginRequestMock($user, $requestBody);
            try {
                $authenticator->authenticate($requestMock);
            } catch (Exception $e) {
                $exceptionWasTrow = true;
                $this->assertInstanceOf(CustomUserMessageAuthenticationException::class, $e, $concreteTestCaseError . 'Exception has an invalid type: ' . $e::class);
            }
            if (!$exceptionWasTrow) {
                $this->assertEquals(0 ,1, $concreteTestCaseError . 'exception was not thrown');
            }
        }

        // Check "User not found" exception
        $exceptionWasTrow = false;
        // Create authenticator instance with exception on trying to identify the user
        $authenticator = $this->createAuthenticatorService(new UserNotFoundException(sprintf('User "%s" not found.', $loginParam)));
        $requestMock = $this->createLoginRequestMock($user);
        try {
            $authenticator->authenticate($requestMock);
        } catch (Exception $e) {
            $exceptionWasTrow = true;
            $this->assertInstanceOf(UserNotFoundException::class, $e, $testCaseBaseError . '"User not found" exception has an invalid type: ' . $e::class);
        }
        if (!$exceptionWasTrow) {
            $this->assertEquals(0 ,1, $testCaseBaseError . '"User not found" exception was not thrown');
        }
    }
}