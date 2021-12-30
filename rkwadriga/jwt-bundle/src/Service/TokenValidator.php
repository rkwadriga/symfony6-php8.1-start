<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\SerializerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\DependencyInjection\TokenValidatorInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenValidationCase;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;

class TokenValidator implements TokenValidatorInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function validate(TokenInterface $token, TokenType $tokenType, array $validationCases = [], array $validationCasesExcluding = []): void
    {
        if (empty($validationCases)) {
            $validationCases = TokenValidationCase::cases();
        }

        if (!empty($validationCasesExcluding)) {
            /** @var $validationCasesExcluding array<TokenValidationCase> */
            $validationCases = array_filter(array_map(function (TokenValidationCase $case) use ($validationCasesExcluding) {
                return in_array($case, $validationCasesExcluding) ? null : $case;
            }, $validationCases));
        }

        if (in_array(TokenValidationCase::EXPIRED, $validationCases)) {
            $currentDataTime = new DateTimeImmutable();
            if ($token->getExpiredAt() <= $currentDataTime) {
                $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::ACCESS_TOKEN_EXPIRED : TokenValidatorException::REFRESH_TOKEN_EXPIRED;
                throw new TokenValidatorException('Token expired', $code);
            }
        }

        if (in_array(TokenValidationCase::TYPE, $validationCases)) {
            $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
            if ($token->getType() !== $tokenType) {
                throw new TokenValidatorException('Invalid token type', $code);
            }
        }

        if (in_array(TokenValidationCase::TOKEN_PARAM_TYPE, $validationCases)) {
            $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
            $head = $token->getHead();
            if (!isset($head['sub']) || $head['sub'] !== $tokenType->value) {
                throw new TokenValidatorException('Invalid token head', $code);
            }
        }

        if (in_array(TokenValidationCase::USER_IDENTIFIER, $validationCases)) {
            $code = $tokenType === TokenType::ACCESS ? TokenValidatorException::INVALID_ACCESS_TOKEN : TokenValidatorException::INVALID_REFRESH_TOKEN;
            $userIdentifier = $this->config->get(ConfigurationParam::USER_IDENTIFIER);
            $payload = $token->getPayload();
            if (!isset($payload[$userIdentifier])) {
                throw new TokenValidatorException('Invalid token payload', $code);
            }
        }

        if (in_array(TokenValidationCase::SIGNATURE, $validationCases)) {
            if ($token->getSignature() !== $token->getCalculatedSignature()) {
                throw new TokenValidatorException('Invalid token', TokenValidatorException::INVALID_SIGNATURE);
            }
        }
    }

    public function validateRefresh(TokenInterface $refreshToken, TokenInterface $accessToken): void
    {
        $userIdentifier = $this->config->get(ConfigurationParam::USER_IDENTIFIER);
        if (!isset($refreshToken->getPayload()[$userIdentifier]) || $refreshToken->getPayload()[$userIdentifier] !== $accessToken->getPayload()[$userIdentifier]) {
            throw new TokenValidatorException('Invalid refresh token payload', TokenValidatorException::INVALID_REFRESH_TOKEN);
        }

        if (!isset($refreshToken->getPayload()['created']) || !isset($accessToken->getPayload()['created'])) {
            return;
        }

        if ($refreshToken->getPayload()['created'] !== $accessToken->getPayload()['created']) {
            throw new TokenValidatorException('Invalid refresh token payload', TokenValidatorException::INVALID_REFRESH_TOKEN);
        }
    }
}