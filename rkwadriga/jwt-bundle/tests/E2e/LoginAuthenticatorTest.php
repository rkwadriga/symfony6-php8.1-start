<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/LoginAuthenticatorTest.php
 */
class LoginAuthenticatorTest extends AbstractE2eTestCase
{
    public function testSuccessfulLogin(): void
    {
        $this->send($this->loginUrl, [
            $this->loginParam => 'test_user@mail.com',
            $this->passwordParam => 'test_passwd',
        ]);

        dd($this->getErrorResponseParams());
        dd($this->getResponseStatusCode(), $this->getResponseParams());
    }
}