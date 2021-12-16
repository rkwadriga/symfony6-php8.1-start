<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\EventSubscriber;

use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationStarted;

class AuthenticationEventSubscriber extends AbstractEventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationStarted::getName() => 'processAuthenticationStarted',
            AuthenticationFinishedSuccessfulEvent::getName() => 'processAuthenticationFinishedSuccessful',
            AuthenticationFinishedUnsuccessfulEvent::getName() => 'processAuthenticationFinishedUnsuccessful',
        ];
    }

    /**
     * Processing "refresh" authentication - checking is refresh_token presented in DB
     *
     * @param AuthenticationStarted $event
     */
    public function processAuthenticationStarted(AuthenticationStarted $event): void
    {
        return;
    }

    public function processAuthenticationFinishedSuccessful(AuthenticationFinishedSuccessfulEvent $event): void
    {
        return;
    }

    public function processAuthenticationFinishedUnsuccessful(AuthenticationFinishedUnsuccessfulEvent $event): void
    {
        return;
    }
}