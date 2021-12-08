<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Entity\TokenInterface;

class TokenCreatingFinishedSuccessfulEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_creating_finished_successful_event';

    public function __construct(
        private TokenInterface $token,
        private array $payload
    ) {}

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function setToken(TokenInterface $token): void
    {
        $this->token = $token;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}