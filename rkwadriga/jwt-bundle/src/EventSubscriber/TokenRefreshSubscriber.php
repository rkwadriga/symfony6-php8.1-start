<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\EventSubscriber;

use Exception;
use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\Services\DbService;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingStartedEvent;
use Rkwadriga\JwtBundle\Exceptions\TokenRefresherException;
use Rkwadriga\JwtBundle\Helpers\TokenHelper;

class TokenRefreshSubscriber extends AbstractEventSubscriber
{
    public function __construct(
        private DbService $dbService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            TokenRefreshingStartedEvent::getName() => 'processTokenRefreshingStarted',
            TokenRefreshingFinishedSuccessfulEvent::getName() => 'processTokenRefreshingFinishedSuccessful',
            TokenRefreshingFinishedUnsuccessfulEvent::getName() => 'processTokenRefreshingFinishedUnsuccessful',
        ];
    }

    public function processTokenRefreshingStarted(TokenRefreshingStartedEvent $event): void
    {
        // Checking refresh token in DB only makes sense when holding refresh tokens in DB is enabled
        if (!$this->dbService->isEnabled()) {
            return;
        }

        // Parse token string to get the token signature
        $tokenData = TokenHelper::parse($event->getRefreshToken()->getToken(), $event->getRefreshToken()->getType());

        // Check is refresh token presented in database
        try {
            $refreshToken = $this->dbService->getRefreshToken($event->getPayload(), end($tokenData));
        } catch (Exception $e) {
            throw new TokenRefresherException('Can not find token: ' . $e->getMessage(), TokenRefresherException::SEARCHING_ERROR, $e);
        }
        if ($refreshToken === null) {
            throw new TokenRefresherException('Invalid refresh token', TokenRefresherException::NOT_FOUND);
        }

        // Check refresh token "createdAt" date
        if ($event->getRefreshToken()->getCreatedAt()->getTimestamp() !== $refreshToken->getCreatedAt()->getTimestamp()) {
            throw new TokenRefresherException('Invalid refresh token', TokenRefresherException::INVALID_CREATED_AT);
        }

        // Remember token object to change it in future
        $event->setExistedRefreshToken($refreshToken);
    }

    public function processTokenRefreshingFinishedSuccessful(TokenRefreshingFinishedSuccessfulEvent $event): void
    {
        // If there is no existed token given - than just nothing to update!
        if (($existedToken = $event->getExistedToken()) === null) {
            return;
        }

        // Token exist. Well, let's update its hash and "created_at" values
        try {
            $this->dbService->updateRefreshToken($existedToken, $event->getNewToken());
        } catch (Exception $e) {
            throw new TokenRefresherException('Can not refresh token: ' . $e->getMessage(), TokenRefresherException::UPDATING_ERROR, $e);
        }
    }

    public function processTokenRefreshingFinishedUnsuccessful(TokenRefreshingFinishedUnsuccessfulEvent $event): void
    {
        return;
    }
}