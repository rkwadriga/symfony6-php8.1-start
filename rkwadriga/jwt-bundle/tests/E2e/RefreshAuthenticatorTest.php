<?php declare(strict_types=1);
/**
 * Created 2021-12-27
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\ClearDatabaseTrait;
use Rkwadriga\JwtBundle\Tests\InstanceTokenTrait;
use Rkwadriga\JwtBundle\Tests\RefreshTokenTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/RefreshAuthenticatorTest.php
 */
class RefreshAuthenticatorTest extends AbstractE2eTestCase
{
    use RefreshTokenTrait;
    use UserInstanceTrait;
    use InstanceTokenTrait;

    public function testSuccessfulRefresh(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate and login user
        $user = $this->createUser();

        // Create token pair and save refresh token to DB
        [$accessToken, $refreshToken] = $this->createTokensPair($this->getDefaultAlgorithm(), $user->getEmail(), null, true);

        $this->refresh($accessToken, $refreshToken);

        $this->checkTokenResponse($user, Response::HTTP_OK);
    }

    private function getDefaultAlgorithm(): Algorithm
    {
        return Algorithm::getByValue($this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM));
    }
}