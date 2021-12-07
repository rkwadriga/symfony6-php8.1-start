<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationFinishedSuccessfulEvent extends AbstractEvent
{
    public const NAME = 'rkwadriga.jwt.authentication_finished_successful_event';

    public function __construct(
        private Request $request,
        private TokenInterface $token
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getTokenInterface(): TokenInterface
    {
        return $this->token;
    }
}