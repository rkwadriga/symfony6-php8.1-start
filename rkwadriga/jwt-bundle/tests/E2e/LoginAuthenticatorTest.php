<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\Tests\InstanceTokenTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/LoginAuthenticatorTest.php
 */
class LoginAuthenticatorTest extends AbstractE2eTestCase
{
    use UserInstanceTrait;
    use InstanceTokenTrait;

    public function testSuccessfulLogin(): void
    {
        // Crate user
        $user = $this->createUser();

        $this->send($this->loginUrl, [
            $this->loginParam => $user->getEmail(),
            $this->passwordParam => self::$password,
        ]);

        $this->checkTokenResponse($user);
    }
}