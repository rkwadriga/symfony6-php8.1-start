<?php
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
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

    protected function refresh(Token|string $accessToken, Token|string $refreshToken): ?array
    {
        [$accessTokenParamName, $refreshTokenParamName, $accessTokenLocation, $refreshTokenLocation, $accessTokenType] = [
            $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME),
            $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_PARAM_NAME),
            $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION),
            $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_LOCATION),
            $this->getConfigDefault(ConfigurationParam::TOKEN_TYPE),
        ];

        if ($accessToken instanceof Token) {
            $accessTokenString = $accessTokenLocation === TokenParamLocation::HEADER->value && $accessTokenType === TokenParamType::BEARER->value
                ? TokenParamType::BEARER->value . ' ' . $accessToken->getToken()
                : $accessToken->getToken();
        } else {
            $accessTokenString = $accessTokenLocation === TokenParamLocation::HEADER->value && $accessTokenType === TokenParamType::BEARER->value
                ? TokenParamType::BEARER->value . ' ' . $accessToken
                : $accessToken;
        }
        if ($refreshToken instanceof Token) {
            $refreshTokenString = $refreshToken->getToken();
        } else {
            $refreshTokenString = $refreshToken;
        }

        $uri = $this->refreshUrl;

        $headers = $body = [];
        switch ($accessTokenLocation) {
            case TokenParamLocation::HEADER->value:
                $accessTokenParamName = 'HTTP_' . strtoupper($accessTokenParamName);
                $headers[$accessTokenParamName] = $accessTokenString;
                break;
            case TokenParamLocation::URI->value:
                $uri = [$uri, $accessTokenParamName => $accessTokenString];
                break;
            default:
                $body[$accessTokenParamName] = $accessTokenString;
                break;
        }

        switch ($refreshTokenLocation) {
            case TokenParamLocation::HEADER->value:
                $refreshTokenParamName = 'HTTP_' . strtoupper($refreshTokenParamName);
                $headers[$refreshTokenParamName] = $refreshTokenString;
                break;
            case TokenParamLocation::URI->value:
                if (!is_array($uri)) {
                    $uri = [$uri];
                }
                $uri[$refreshTokenParamName] = $refreshTokenString;
                break;
            default:
                $body[$refreshTokenParamName] = $refreshTokenString;
                break;
        }

        $this->send($uri, $body, $headers);

        if (!in_array($this->getResponseStatusCode(), [Response::HTTP_CREATED, Response::HTTP_OK])) {
            return null;
        }

        return $this->getResponseParams();
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