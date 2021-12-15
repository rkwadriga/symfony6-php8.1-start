<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\EventSubscriber;

use Rkwadriga\JwtBundle\DependencyInjection\Services\DbService;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingStartedEvent;

class TokenCreateEventSubscriber extends AbstractEventSubscriber
{
    public function __construct(
        private DbService $dbService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            TokenCreatingStartedEvent::getName() => 'processTokenCreatingStarted',
            TokenCreatingFinishedSuccessfulEvent::getName() => 'processTokenCreatingFinishedSuccessful',
            TokenCreatingFinishedUnsuccessfulEvent::getName() => 'processTokenCreatingFinishedUnsuccessful'
        ];
    }

    public function processTokenCreatingStarted(TokenCreatingStartedEvent $event): void
    {
        $this->dbService->checkTokensLimit($event->getPayload());
    }

    public function processTokenCreatingFinishedSuccessful(TokenCreatingFinishedSuccessfulEvent $event): void
    {
        $this->dbService->writeToken($event->getToken(), $event->getPayload());
    }

    public function processTokenCreatingFinishedUnsuccessful(TokenCreatingFinishedUnsuccessfulEvent $event): void
    {
        return;
    }
}