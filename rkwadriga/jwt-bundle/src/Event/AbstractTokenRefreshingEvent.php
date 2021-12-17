<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;

abstract class AbstractTokenRefreshingEvent extends AbstractTokenEvent
{
    public function __construct(
        private TokenInterface $oldRefreshToken,
        private TokenInterface $newRefreshToken,
        private TokenInterface $accessToken
    ) {}

    public function getOldRefreshToken(): TokenInterface
    {
        return $this->oldRefreshToken;
    }

    public function setOldRefreshToken(TokenInterface $oldRefreshToken): void
    {
        $this->oldRefreshToken = $oldRefreshToken;
    }

    public function getNewRefreshToken(): TokenInterface
    {
        return $this->newRefreshToken;
    }

    public function setNewRefreshToken(TokenInterface $newRefreshToken): void
    {
        $this->newRefreshToken = $newRefreshToken;
    }

    public function getAccessToken(): TokenInterface
    {
        return $this->accessToken;
    }

    public function setAccessToken(TokenInterface $accessToken): void
    {
        $this->accessToken = $accessToken;
    }
}