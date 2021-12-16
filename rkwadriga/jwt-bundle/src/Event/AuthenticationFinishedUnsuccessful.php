<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Enum\AuthenticationType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationFinishedUnsuccessful extends AbstractAuthenticationEvent
{
    protected static string $name = 'rkwadriga.jwt.authentication_finished_unsuccessful';

    public function __construct(
        AuthenticationType $authenticationType,
        private Request $request,
        private AuthenticationException $exception,
        private ?Response $response = null
    ) {
        parent::__construct($authenticationType);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getException(): AuthenticationException
    {
        return $this->exception;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }
}