<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface as UserTokenInterface;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationFinishedSuccessfulEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.authentication_finished_successful_event';

    public function __construct(
        private Request $request,
        private UserTokenInterface $userToken,
        private TokenInterface $token,
        private ?Response $response = null
    ) {}

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUserToken(): UserTokenInterface
    {
        return $this->userToken;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}