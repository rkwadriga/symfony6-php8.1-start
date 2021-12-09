<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

class TokenRefreshingStartedEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_refreshing_started_event';

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