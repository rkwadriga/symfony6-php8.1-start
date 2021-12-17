<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;

class TokenCreatingFinishedUnsuccessful extends AbstractTokenCreatingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_creating_finished_unsuccessful';

    public function __construct(
        TokenCreationContext $creationContext,
        private Exception $exception,
        private array $head,
        private array $payload,
        private TokenType $tokenType
    ) {
        parent::__construct($creationContext);
    }

    public function getException(): Exception
    {
        return $this->exception;
    }

    public function setException(Exception $exception): void
    {
        $this->exception = $exception;
    }

    public function getHead(): array
    {
        return $this->head;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTokenType(): TokenType
    {
        return $this->tokenType;
    }
}