<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

class TokenCreatingStartedEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_creating_started_event';

    public function __construct(
        private array $payload
    ) {}

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }


}