<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationFinishedUnsuccessfulEvent extends AbstractEvent
{
    public const NAME = 'rkwadriga.jwt.authentication_finished_unsuccessful_event';

    public function __construct(
        private Request $request,
        private AuthenticationException $exception
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getException(): AuthenticationException
    {
        return $this->exception;
    }
}