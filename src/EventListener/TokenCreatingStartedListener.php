<?php declare(strict_types=1);
/**
 * Created 2021-12-10
 * Author Dmitry Kushneriov
 */

namespace App\EventListener;

use Rkwadriga\JwtBundle\Event\TokenCreatingStarted;

class TokenCreatingStartedListener
{
    public function onTokenCreatingStarted(TokenCreatingStarted $event): void
    {
        //dd($event);
    }
}