<?php declare(strict_types=1);
/**
 * Created 2021-12-10
 * Author Dmitry Kushneriov
 */

namespace App\EventListener;

use Rkwadriga\JwtBundle\Event\TokenCreatingStartedEvent;

class TokenCreatingStartedListener
{
    public function onTokenCreatingStarted(TokenCreatingStartedEvent $event): void
    {
        dd($event);
    }
}