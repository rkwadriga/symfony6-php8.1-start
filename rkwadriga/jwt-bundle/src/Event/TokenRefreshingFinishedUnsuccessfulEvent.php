<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Exception;

class TokenRefreshingFinishedUnsuccessfulEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_refreshing_finished_unsuccessful_event';

    public function __construct(
        private Exception $exception,
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

    public function getPayload(): array
    {
        return $this->payload;
    }
}