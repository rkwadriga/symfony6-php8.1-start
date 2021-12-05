<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Helpers;

use Rkwadriga\JwtBundle\Exceptions\TokenException;

class TokenHelper
{
    public const SEPARATOR = '.';

    public static function toContentPartString(array $header, array $payload): string
    {
        return implode(self::SEPARATOR, [self::toBase64String($header), self::toBase64String($payload)]);
    }

    public static function serialize(string $contentPart, string $signature): string
    {
        $result = implode(self::SEPARATOR, [$contentPart, base64_encode($signature)]);
        return str_replace('=', '', $result);
    }

    public static function parse(string $token, string $type): array
    {
        [$header, $payload, $signature] = self::deserialize($token);
        if (!isset($header['sub']) || $header['sub'] !== $type) {
            throw new TokenException('Invalid token', TokenException::INVALID_TOKEN_FORMAT);
        }
        if (!isset($payload['exp']) || !is_numeric($payload['exp'])) {
            throw new TokenException('Invalid token', TokenException::INVALID_TOKEN_FORMAT);
        }

        return [$header, $payload, $signature];
    }

    private static function deserialize(string $token): array
    {
        $parts = explode(self::SEPARATOR, $token);
        if (count($parts) !== 3) {
            throw new TokenException(
                sprintf('Invalid token format: it should have 3 parts separated by "%s"', self::SEPARATOR),
                TokenException::INVALID_TOKEN_FORMAT
            );
        }

        [$header, $payload, $signature] = $parts;
        return [
            self::fromBase64String($header),
            self::fromBase64String($payload),
            self::fromBase64String($signature, false)
        ];
    }

    private static function fromBase64String(string $encoded, bool $jsonEncoded = true): array|string
    {
        $message = 'Invalid token format. Error: ';
        $decoded = base64_decode($encoded);
        if ($decoded === false) {
            if ($php_errormsg) {
                $message .= $php_errormsg;
            } else {
                $message .= 'invalid base64 string';
            }
            throw new TokenException($message, TokenException::INVALID_TOKEN_FORMAT);
        }
        if (!$jsonEncoded) {
            return $decoded;
        }

        $data = json_decode($decoded, true);
        if ($data === null) {
            if ($jsonError = json_last_error_msg()) {
                $message .= $jsonError;
            } else {
                $message .= 'invalid json string';
            }
            throw new TokenException($message, TokenException::INVALID_TOKEN_FORMAT);
        }

        return $data;
    }

    private static function toBase64String(array $data): string
    {
        return base64_encode(json_encode($data));
    }
}