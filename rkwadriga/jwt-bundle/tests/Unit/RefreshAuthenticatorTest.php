<?php declare(strict_types=1);
/**
 * Created 2021-12-24
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Request;

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
}