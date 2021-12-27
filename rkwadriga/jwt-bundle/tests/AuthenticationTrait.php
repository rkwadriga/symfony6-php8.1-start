<?php
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Symfony\Component\HttpFoundation\Response;

trait AuthenticationTrait
{
    private ?string $token = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->logout();
    }

    protected function login(mixed $userID = null, mixed $password = null): ?array
    {
        $this->token = null;

        $loginParams = [];
        if ($userID !== false) {
            $loginParams[$this->loginParam] = $userID ?? self::$userID;
        }
        if ($password !== false) {
            $loginParams[$this->passwordParam] = $password ?? self::$password;
        }

        $this->send($this->loginUrl, $loginParams);

        if (!in_array($this->getResponseStatusCode(), [Response::HTTP_CREATED, Response::HTTP_OK])) {
            return null;
        }

        $result = $this->getResponseParams();
        if (isset($result['accessToken'])) {
            $this->token = $result['accessToken'];
        }

        return $result;
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
}