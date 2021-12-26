<?php declare(strict_types=1);
/**
 * Created 2021-12-26
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/JwtAuthenticatorTest.php
 */
class JwtAuthenticatorTest extends AbstractUnitTestCase
{
    use AuthenticationTrait;

    public function testSupports(): void
    {
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
}