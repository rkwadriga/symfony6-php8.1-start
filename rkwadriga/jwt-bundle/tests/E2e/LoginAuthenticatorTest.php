<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\Tests\InstanceTokenTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Response;

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

        // Login the user
        $this->login();

        $this->checkTokenResponse($user);
    }

    public function testInvalidCredentialsException(): void
    {
        // Check not existed user login
        $this->login();
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');

        // Create user and check invalid login and password
        $user = $this->createUser();

        $this->login('INVALID_EMAIL');
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');

        $this->login($user->getEmail(), 'INVALID_PASSWORD');
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');

        $this->login('INVALID_EMAIL', 'INVALID_PASSWORD');
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');
    }

    public function testMissedRequiredParamsException(): void
    {
        // Create user and check login without required fields
        $user = $this->createUser();

        $this->login(false);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, ['Params', 'required']);

        $this->login($user->getEmail(), false);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, ['Params', 'required']);

        $this->login(false, false);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, ['Params', 'required']);
    }
}