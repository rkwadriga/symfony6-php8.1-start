<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

class TokenRefreshingStarted extends AbstractTokenRefreshingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_refreshing_started';
}