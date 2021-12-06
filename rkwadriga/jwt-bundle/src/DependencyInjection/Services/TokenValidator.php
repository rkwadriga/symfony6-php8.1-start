<?php declare(strict_types=1);
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use DateTime;
use Rkwadriga\JwtBundle\Entities\TokenValidatableInterface;
use Rkwadriga\JwtBundle\Entities\Token;
use Rkwadriga\JwtBundle\Exceptions\TokenValidatorException;

class TokenValidator
{
    public function validateExpiredAt(TokenValidatableInterface $token, string $exceptionClass = TokenValidatorException::class): void
    {
        if ($token->getExpiredAt() > new DateTime()) {
            return;
        }

        if ($token->getType() === Token::ACCESS) {
            $error = 'Access token expired';
            $code = TokenValidatorException::ACCESS_TOKEN_EXPIRED;
        } else {
            $error = 'Refresh token expired';
            $code = TokenValidatorException::REFRESH_TOKEN_EXPIRED;
        }
        throw new $exceptionClass($error, $code, new TokenValidatorException($error, $code));
    }

    public function validatePayload(TokenValidatableInterface $token, array $params, string $exceptionClass = TokenValidatorException::class): void
    {
        $payload = $token->getPayload();

        if ($token->getType() === Token::ACCESS) {
            $error = 'Invalid access token';
            $code = TokenValidatorException::INVALID_ACCESS_TOKEN;
        } else {
            $error = 'Invalid refresh token';
            $code = TokenValidatorException::INVALID_REFRESH_TOKEN;
        }
        $selfException = $exceptionClass !== TokenValidatorException::class ? new TokenValidatorException($error, $code) : null;

        foreach ($params as $name => $isRequired) {
            if (is_string($isRequired)) {
                [$name, $isRequired, $type, $value] = [$isRequired, true, 'string', null];
            } elseif (is_array($isRequired)) {
                [$isRequired, $type, $value] = [
                    $isRequired['required'] ?? true,
                    $isRequired['type'] ?? 'string',
                    $isRequired['value'] ?? null
                ];
            } else {
                [$isRequired, $type, $value] = [true, 'string', null];
            }

            if (!isset($payload[$name])) {
                if (!$isRequired) {
                    return;
                } else {
                    throw new $exceptionClass($error, $code, $selfException);
                }
            }

            if ($type !== false && $type !== gettype($payload[$name])) {
                throw new $exceptionClass($error, $code, $selfException);
            }

            if ($value !== null && $value !== $payload[$name]) {
                throw new $exceptionClass($error, $code, $selfException);
            }
        }
    }
}