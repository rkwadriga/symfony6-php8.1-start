<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Symfony\Component\HttpFoundation\Request;

class AuthenticationStartedEvent extends AbstractEvent
{
    public const NAME = 'rkwadriga_jwt_authentication_started';

    public function __construct(
        private Request $request
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }
}