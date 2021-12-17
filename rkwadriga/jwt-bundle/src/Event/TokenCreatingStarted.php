<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;

class TokenCreatingStarted extends AbstractTokenCreatingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_creating_started';

    public function __construct(
        TokenCreationContext $creationContext,
        private array $payload,
        private TokenType $tokenType
    ) {
        parent::__construct($creationContext);
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getTokenType(): TokenType
    {
        return $this->tokenType;
    }
}