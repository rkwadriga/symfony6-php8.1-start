<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Api;

use Rkwadriga\JwtBundle\Tests\Api\fixtures\UserFixture;
use Rkwadriga\JwtBundle\Tests\Api\Helpers\ApiTestsHelperMethodsTrait;
use Rkwadriga\JwtBundle\Tests\Api\Helpers\ApiTestsSetupTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Run test rkwadriga/jwt-bundle/tests/Api/ProfileTest.php
class ProfileTest extends WebTestCase
{
    use ApiTestsHelperMethodsTrait;
    use ApiTestsSetupTrait;

    public function testGetProfileSuccessful()
    {
        $user = $this->createUser();
        $response = $this->request('get_profile');

        dd($response);
    }
}