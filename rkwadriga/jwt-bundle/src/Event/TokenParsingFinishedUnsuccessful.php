<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;

class TokenParsingFinishedUnsuccessful extends AbstractTokenParsingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_parsing_finished_unsuccessful';

    public function __construct(
        private Exception $exception,
        private string $token,
        private TokenType $tokenType,
        private array $head,
        private array $payload
    ) {}

    public function getException(): Exception
    {
        return $this->exception;
    }

    public function setException(Exception $exception): void
    {
        $this->exception = $exception;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTokenType(): TokenType
    {
        return $this->tokenType;
    }

    public function getHead(): array
    {
        return $this->head;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}