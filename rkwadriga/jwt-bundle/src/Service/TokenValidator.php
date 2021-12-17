<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\DependencyInjection\TokenValidatorInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;

class TokenValidator implements TokenValidatorInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function validate(TokenInterface $token, TokenType $tokenType): void
    {
        $currentDataTime = new DateTimeImmutable();
        if ($token->getExpiredAt() <= $currentDataTime) {
            $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::ACCESS_TOKEN_EXPIRED : TokenValidatorException::REFRESH_TOKEN_EXPIRED;
            throw new TokenValidatorException('Token expired', $code);
        }

        $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
        if ($token->getType() !== $tokenType) {
            throw new TokenValidatorException('Invalid token type', $code);
        }

        $userIdentifier = $this->config->get(ConfigurationParam::USER_IDENTIFIER);
        $payload = $token->getPayload();
        if (!isset($payload[$userIdentifier])) {
            throw new TokenValidatorException('Invalid token payload', $code);
        }
    }

    public function validateRefresh(TokenInterface $refreshToken, TokenInterface $accessToken): void
    {
        // TODO: Implement validateRefresh() method.
    }
}