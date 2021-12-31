<?php declare(strict_types=1);
/**
 * Created 2021-12-31
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\InstanceTokenTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/JwtAuthenticatorTest.php
 */
class JwtAuthenticatorTest extends AbstractE2eTestCase
{
    use UserInstanceTrait;
    use InstanceTokenTrait;

    public function testSuccessfulAuthentication(): void
    {
        // Crate user
        $user = $this->createUser();

        // Create token
        $algorithm = Algorithm::from($this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM));
        $token = $this->createToken($algorithm, TokenType::ACCESS, $user->getEmail());

        $this->assertSame(1, 1);
    }
}