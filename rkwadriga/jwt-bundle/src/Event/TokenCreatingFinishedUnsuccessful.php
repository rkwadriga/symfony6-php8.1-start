<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Exception;

class TokenCreatingFinishedUnsuccessful extends AbstractTokenCreatingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_creating_finished_unsuccessful';

    public function __construct(
        private Exception $exception,
        private array $payload
    ) {}

    public function getException(): Exception
    {
        return $this->exception;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setException(Exception $exception): void
    {
        $this->exception = $exception;
    }


}