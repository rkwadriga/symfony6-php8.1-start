<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Entity\RefreshToken;
use Rkwadriga\JwtBundle\Entity\TokenInterface;

class TokenRefreshingFinishedSuccessfulEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_refreshing_finished_successful_event';

    public function __construct(
        private ?RefreshToken $existedToken,
        private TokenInterface $newToken
    ) {}

    public function getExistedToken(): ?RefreshToken
    {
        return $this->existedToken;
    }

    public function setExistedToken(RefreshToken $existedToken): void
    {
        $this->existedToken = $existedToken;
    }

    public function getNewToken(): TokenInterface
    {
        return $this->newToken;
    }

    public function setNewToken(TokenInterface $newToken): void
    {
        $this->newToken = $newToken;
    }
}