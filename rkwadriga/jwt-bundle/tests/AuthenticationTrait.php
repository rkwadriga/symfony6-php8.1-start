<?php
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Exception;
use Rkwadriga\JwtBundle\Authenticator\LoginAuthenticator;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

trait AuthenticationTrait
{
    private ?string $token = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->logout();
    }

    protected function logout(): void
    {
        $this->token = null;
    }

    protected function setToken(string $token): void
    {
        $this->token = $token;
    }

    protected function getToken(): ?string
    {
        return $this->token;
    }

    protected function createAuthenticatorService(User $user, bool $passwordVerifyingResult = true, ?Exception $exception = null): LoginAuthenticator
    {
        // Mock password hasher and UserProvider
        $hasherFactoryMock = $this->mockPasswordHasherFactory($user->getPassword(), $passwordVerifyingResult);
        $userProviderMock = $this->mockUserProvider($exception ?? $user);

        return $this->createLoginAuthenticatorInstance($userProviderMock, $hasherFactoryMock);
    }

    protected function createLoginRequestMock(User $user, array|string $bodyParams = []): Request
    {
        if (is_string($bodyParams)) {
            $body = $bodyParams;
        } else {
            if (empty($bodyParams)) {
                [$loginParam, $passwordParam] = [$this->getConfigDefault(ConfigurationParam::LOGIN_PARAM), $this->getConfigDefault(ConfigurationParam::PASSWORD_PARAM)];
                $bodyParams = [$loginParam => $user->getEmail(), $passwordParam => $user->getPassword()];
            }
            $body = json_encode($bodyParams);
        }

        return $this->createMock(Request::class, ['getContent' => $body]);
    }
}