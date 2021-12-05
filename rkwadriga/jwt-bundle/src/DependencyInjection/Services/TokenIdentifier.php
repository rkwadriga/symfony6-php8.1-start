<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Entities\Token;
use Rkwadriga\JwtBundle\Exceptions\TokenIdentifierException;
use Symfony\Component\HttpFoundation\Request;

class TokenIdentifier
{
    public const LOCATION_HEADER = 'header';
    public const LOCATION_URI = 'uri';
    public const LOCATION_BODY = 'body';
    public const TYPE_BEARER = 'Bearer';
    public const TYPE_SIMPLE = 'Simple';

    public function __construct(
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

    public function identify(Request $request): Token
    {
        $accessToken = $this->getToken($request, Token::ACCESS);
        dd($accessToken);
    }

    private function getToken(Request $request, string $type, bool $throwNotFoundException = true): string
    {
        if ($type === Token::ACCESS) {
            $location = $this->accessTokenLocation;
            $paramName = $this->accessTokenParamName;
        } else {
            $location = $this->accessTokenLocation;
            $paramName = $this->accessTokenParamName;
        }

        $token = match ($location) {
            self::LOCATION_HEADER => $request->headers->get($paramName),
            self::LOCATION_URI => $request->request->get($paramName),
            self::LOCATION_BODY => $this->getTokenFromRequestBody($request, $paramName),
            default => null
        };

        dd($token);

        if ($token === null && $throwNotFoundException) {
            throw new TokenIdentifierException('No token', TokenIdentifierException::TOKEN_NOT_FOUND);
        }

        return new $token;
    }

    private function getTokenFromRequestBody(Request $request, string $paramName): ?string
    {
        dd($paramName);
    }
}