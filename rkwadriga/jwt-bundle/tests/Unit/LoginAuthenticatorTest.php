<?php declare(strict_types=1);
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\CreateUserTableTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/LoginAuthenticatorTest.php
 */
class LoginAuthenticatorTest extends AbstractUnitTestCase
{
    use CreateUserTableTrait;

    public function AtestSupports(): void
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


        // Mock request
        [$loginParam, $passwordParam] = [$this->getConfigDefault(ConfigurationParam::LOGIN_PARAM), $this->getConfigDefault(ConfigurationParam::PASSWORD_PARAM)];
        //$requestMock = $this->createMock(Request::class, ['getContent' => json_encode([])]);
        $this->assertTrue(true);
    }
}