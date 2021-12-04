<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Api;

use Rkwadriga\JwtBundle\Tests\Api\fixtures\UserFixture;
use Rkwadriga\JwtBundle\Tests\Api\Helpers\ApiTestsHelperMethodsTrait;
use Rkwadriga\JwtBundle\Tests\Api\Helpers\ApiTestsSetupTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Run test rkwadriga/jwt-bundle/tests/Api/AuthenticationTest.php
class AuthenticationTest extends WebTestCase
{
    use ApiTestsHelperMethodsTrait;
    use ApiTestsSetupTrait;

    public function testLoginSuccessful()
    {
        $user = $this->createUser();
        $response = $this->request($this->loginUrl, [
            $this->loginParam => UserFixture::EMAIL,
            $this->passwordParam => UserFixture::PASSWORD
        ]);

        dd($response);
    }
}