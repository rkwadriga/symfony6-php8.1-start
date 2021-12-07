<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\EventSubscriber;

use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationStartedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuthenticationEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            AuthenticationStartedEvent::NAME => 'processAuthenticationStarted',
            AuthenticationFinishedSuccessfulEvent::NAME => 'processAuthenticationFinishedSuccessful',
            AuthenticationFinishedUnsuccessfulEvent::NAME => 'processAuthenticationFinishedUnsuccessful',
        ];
    }

    public function processAuthenticationStarted(AuthenticationStartedEvent $event): void
    {
        dd($event);
    }

    public function processAuthenticationFinishedSuccessful(AuthenticationFinishedSuccessfulEvent $event): void
    {
        dd($event);
    }

    public function processAuthenticationFinishedUnsuccessful(AuthenticationFinishedUnsuccessfulEvent $event): void
    {
        dd($event);
    }
}