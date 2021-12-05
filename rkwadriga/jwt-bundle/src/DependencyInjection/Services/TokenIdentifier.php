<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Entities\Token;
use Rkwadriga\JwtBundle\Entities\TokenData;
use Rkwadriga\JwtBundle\Exceptions\TokenIdentifierException;
use Rkwadriga\JwtBundle\Helpers\TokenHelper;
use Symfony\Component\HttpFoundation\Request;

class TokenIdentifier
{
    public const LOCATION_HEADER = 'header';
    public const LOCATION_URI = 'uri';
    public const LOCATION_BODY = 'body';
    public const TYPE_BEARER = 'Bearer';
    public const TYPE_SIMPLE = 'Simple';

    public function __construct(
        private Encoder $encoder,
        private string $accessTokenLocation,
        private string $accessTokenParamName,
        private string $refreshTokenLocation,
        private string $refreshTokenParamName,
        private string $tokenType,
    ) {}

    public static array $allowedLocations = [
        self::LOCATION_HEADER,
        self::LOCATION_URI,
        self::LOCATION_BODY
    ];

    public static array $allowedTypes = [
        self::TYPE_BEARER,
        self::TYPE_SIMPLE
    ];

    /**
     * @param Request $request
     *
     * @throws TokenIdentifierException
     *
     * @return array<TokenData, ?TokenData>
     */
    public function identify(Request $request): array
    {
        $accessToken = $this->getToken($request, Token::ACCESS);
        $accessToken = new TokenData(...$accessToken);

        $refreshToken = $this->getToken($request, Token::REFRESH);
        if ($refreshToken !== null) {
            $refreshToken = new TokenData(...$refreshToken);
        }

        return [$accessToken, $refreshToken];
    }

    private function getToken(Request $request, string $type): ?array
    {
        if ($type === Token::ACCESS) {
            $location = $this->accessTokenLocation;
            $paramName = $this->accessTokenParamName;
        } else {
            $location = $this->refreshTokenLocation;
            $paramName = $this->refreshTokenParamName;
        }

        $token = match ($location) {
            self::LOCATION_HEADER => $this->getTokenFromHeader($request, $paramName),
            self::LOCATION_URI => $request->get($paramName),
            self::LOCATION_BODY => $this->getTokenFromBody($request, $paramName),
            default => null
        };

        if ($token === null) {
            if ($type === Token::ACCESS) {
                throw new TokenIdentifierException('No token', TokenIdentifierException::TOKEN_NOT_FOUND);
            } else {
                return null;
            }
        }

        // Parse token
        [$header, $payload, $signature] = TokenHelper::parse($token, $type);

        // Check token signature
        $contentPart = TokenHelper::toContentPartString($header, $payload);
        if ($signature !== $this->encoder->encode($contentPart)) {
            throw new TokenIdentifierException('Invalid token', TokenIdentifierException::INVALID_TOKEN);
        }

        return [$token, array_merge($header, $payload)];
    }

    private function getTokenFromHeader(Request $request, string $paramName): ?string
    {
        $token = $request->headers->get($paramName);
        if ($token === null) {
            return null;
        }
        if ($this->tokenType === self::TYPE_BEARER) {
            if (strpos($token, $this->tokenType . ' ') !== 0) {
                throw new TokenIdentifierException('Invalid token', TokenIdentifierException::INVALID_TOKEN);
            }
            $token = str_replace($this->tokenType . ' ', '', $token);
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