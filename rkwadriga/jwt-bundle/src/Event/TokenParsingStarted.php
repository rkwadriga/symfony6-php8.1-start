<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\DependencyInjection\TokenType;

class TokenParsingStarted extends AbstractTokenParsingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_parsing_started';

    public function __construct (
        private string $token,
        private TokenType $tokenType
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getType(): TokenType
    {
        return $this->tokenType;
    }
}