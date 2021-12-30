<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\TokenIdentifierInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Symfony\Component\HttpFoundation\Request;

class TokenIdentifier implements TokenIdentifierInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function identify(Request $request, TokenType $tokenType): string
    {
        if ($tokenType === TokenType::ACCESS) {
            $location = $this->config->get(ConfigurationParam::ACCESS_TOKEN_LOCATION);
            $paramName = $this->config->get(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME);
        } else {
            $location = $this->config->get(ConfigurationParam::REFRESH_TOKEN_LOCATION);
            $paramName = $this->config->get(ConfigurationParam::REFRESH_TOKEN_PARAM_NAME);
        }
        $location = TokenParamLocation::from($location);

        $token = match($location) {
            TokenParamLocation::HEADER => $this->getTokenFromHeader($request, $paramName),
            TokenParamLocation::URI => $request->get($paramName),
            TokenParamLocation::BODY => $this->getTokenFromBody($request, $paramName),
        };

        if ($token === null) {
            $code = $tokenType === TokenType::ACCESS ? TokenIdentifierException::ACCESS_TOKEN_MISSED : TokenIdentifierException::REFRESH_TOKEN_MISSED;
            throw new TokenIdentifierException('Token not found', $code);
        }

        return $token;
    }

    private function getTokenFromHeader(Request $request, string $paramName): ?string
    {
        $token = $request->headers->get($paramName);
        if ($token === null) {
            return null;
        }

        $tokenParamType = $this->config->get(ConfigurationParam::TOKEN_TYPE);
        if ($tokenParamType === TokenParamType::BEARER->value) {
            if (strpos($token, $tokenParamType . ' ') !== 0) {
                throw new TokenIdentifierException('Invalid token', TokenIdentifierException::INVALID_ACCESS_TOKEN);
            }
            $token = str_replace($tokenParamType . ' ', '', $token);
        }

        return $token;
    }

    private function getTokenFromBody(Request $request, string $paramName): ?string
    {
        if ($request->getContentType() !== 'json') {
            return $request->get($paramName);
        }
        $requestParams = json_decode($request->getContent(), true);
        if ($requestParams === null) {
            return null;
        }

        return $requestParams[$paramName] ?? null;
    }
}