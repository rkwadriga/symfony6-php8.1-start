<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\EventSubscriber;

use Rkwadriga\JwtBundle\DependencyInjection\Services\DbService;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingStartedEvent;

class TokenRefreshSubscriber extends AbstractEventSubscriber
{
    public function __construct(
        private DbService $dbService,
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
        return;
    }

    public function processTokenRefreshingFinishedSuccessful(TokenRefreshingFinishedSuccessfulEvent $event): void
    {
        return;
    }

    public function processTokenRefreshingFinishedUnsuccessful(TokenRefreshingFinishedUnsuccessfulEvent $event): void
    {
        return;
    }
}