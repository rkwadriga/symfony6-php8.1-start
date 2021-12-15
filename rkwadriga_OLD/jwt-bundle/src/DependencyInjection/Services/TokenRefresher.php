<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Exception;
use Rkwadriga\JwtBundle\Entity\TokenData;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingStartedEvent;
use Rkwadriga\JwtBundle\EventSubscriber\TokenRefreshSubscriber;
use Rkwadriga\JwtBundle\Exception\TokenRefresherException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TokenRefresher
{
    public function __construct(
        private EventDispatcherInterface $eventsDispatcher,
        private DbService $dbService,
        private TokenGenerator $generator
    ) {
        $this->eventsDispatcher->addSubscriber(new TokenRefreshSubscriber($this->dbService));
    }

    public function refreshToken(array $payload, TokenData $refreshToken): TokenInterface
    {
        try {
            // This event will check is db holding enabled for refresh_tokens and will search given refresh_token in DB.
            // Also, it allows to change the payload
            $event = new TokenRefreshingStartedEvent($payload, $refreshToken);
            $this->eventsDispatcher->dispatch($event, TokenRefreshingStartedEvent::getName());
            [$payload, $existedToken] = [$event->getPayload(), $event->getExistedRefreshToken()];
            // It shouldn't be null but just in case...
            if ($this->dbService->isEnabled() && $existedToken === null) {
                throw new TokenRefresherException('Refresh token kan not be null to updating it', TokenRefresherException::NOT_FOUND);
            }

            // Generating new token without dispatching "token_generation" events
            $newToken = $this->generator->createToken($payload);

            // This event will update refresh_token in DB if DB holding is enabled.
            // Also, it allows changing generated token data
            $event = new TokenRefreshingFinishedSuccessfulEvent($existedToken, $newToken);
            $this->eventsDispatcher->dispatch($event, TokenRefreshingFinishedSuccessfulEvent::getName());
            return $event->getNewToken();
        } catch (Exception $e) {
            // This event allows processing token refreshing exceptions
            $event = new TokenRefreshingFinishedUnsuccessfulEvent($e, $payload);
            $this->eventsDispatcher->dispatch($event, TokenRefreshingFinishedUnsuccessfulEvent::getName());
            throw $event->getException();
        }
    }
}