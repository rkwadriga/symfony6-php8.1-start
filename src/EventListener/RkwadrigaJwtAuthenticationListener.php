<?php declare(strict_types=1);
/**
 * Created 2021-12-10
 * Author Dmitry Kushneriov
 */

namespace App\EventListener;

class RkwadrigaJwtAuthenticationListener
{
    public function onAuthenticationStarted($event)
    {
        //dd($event);
    }
}