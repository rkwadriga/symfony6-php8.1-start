<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Entity\TokenInterface;

class TokenRefreshingFinishedSuccessfulEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_refreshing_finished_successful_event';

    public function __construct(
        private TokenInterface $token
    ) {}

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function setToken(TokenInterface $token): void
    {
        $this->token = $token;
    }
}