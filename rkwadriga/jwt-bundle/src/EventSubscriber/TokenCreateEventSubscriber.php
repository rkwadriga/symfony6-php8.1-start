<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\EventSubscriber;

use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingStartedEvent;

class TokenCreateEventSubscriber extends AbstractEventSubscriber
{
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
        return;
    }

    public function processTokenCreatingFinishedSuccessful(TokenCreatingFinishedSuccessfulEvent $event): void
    {
        return;
    }

    public function processTokenCreatingFinishedUnsuccessful(TokenCreatingFinishedUnsuccessfulEvent $event): void
    {
        return;
    }
}