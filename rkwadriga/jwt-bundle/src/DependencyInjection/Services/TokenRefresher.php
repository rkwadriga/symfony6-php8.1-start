<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Exception;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingStartedEvent;
use Rkwadriga\JwtBundle\EventSubscriber\TokenRefreshSubscriber;
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

    public function refreshToken(array $payload): TokenInterface
    {
        try {
            // This event allows to change payload
            $event = new TokenRefreshingStartedEvent($payload);
            $this->eventsDispatcher->dispatch($event, $event::getName());
            $payload = $event->getPayload();

            $token = $this->generator->generate($payload);

            // This event allows to change token data
            $event = new TokenRefreshingFinishedSuccessfulEvent($token);
            $this->eventsDispatcher->dispatch($event, $event::getName());
            return $event->getToken();
        } catch (Exception $e) {
            // This event allow to process token refreshing exceptions
            $event = new TokenRefreshingFinishedUnsuccessfulEvent($e);
            $this->eventsDispatcher->dispatch($event, $event::getName());
            throw $event->getException();
        }
    }
}