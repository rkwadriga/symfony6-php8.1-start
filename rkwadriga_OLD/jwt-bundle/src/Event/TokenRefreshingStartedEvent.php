<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Entity\RefreshToken;
use Rkwadriga\JwtBundle\Entity\TokenData;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Rkwadriga\JwtBundle\Exception\EventException;

class TokenRefreshingStartedEvent extends AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.token_refreshing_started_event';

    private ?RefreshToken $existedRefreshToken = null;

    public function __construct(
        private array $payload,
        private TokenData $refreshToken
    ) {
        // Check token type - only "refresh_token" allowed
        if ($this->refreshToken->getType() !== TokenInterface::REFRESH) {
            throw new EventException(sprintf('Invalid token type: "%s". For event "%s" only "%s" tokens allowed',
                $this->refreshToken->getType(),
                self::getName(),
                TokenInterface::REFRESH
            ), EventException::INVALID_DATA);
        }
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getRefreshToken(): TokenData
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(TokenData $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getExistedRefreshToken(): ?RefreshToken
    {
        return $this->existedRefreshToken;
    }

    public function setExistedRefreshToken(?RefreshToken $existedToken): void
    {
        $this->existedRefreshToken = $existedToken;
    }
}