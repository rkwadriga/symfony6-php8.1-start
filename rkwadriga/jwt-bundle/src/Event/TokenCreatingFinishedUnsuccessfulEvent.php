<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Exception;

class TokenCreatingFinishedUnsuccessfulEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_creating_finished_unsuccessful_event';

    public function __construct(
        private Exception $exception
    ) {}

    public function getException(): Exception
    {
        return $this->exception;
    }

    public function setException(Exception $exception): void
    {
        $this->exception = $exception;
    }


}