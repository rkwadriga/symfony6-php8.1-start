<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Enum\AuthenticationType;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationFinishedSuccessful extends AbstractAuthenticationEvent
{
    protected static string $name = 'rkwadriga.jwt.authentication_finished_successful';

    public function __construct(
        AuthenticationType $authenticationType,
        private ?Response $response = null,
    ) {
        parent::__construct($authenticationType);
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}