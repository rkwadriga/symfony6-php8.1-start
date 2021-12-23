<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/LoginAuthenticatorTest.php
 */
class LoginAuthenticatorTest extends AbstractE2eTestCase
{
    public function testSuccessfulLogin(): void
    {
        dd($this->router->createRoute($this->getConfigDefault(ConfigurationParam::LOGIN_URL)));
    }
}