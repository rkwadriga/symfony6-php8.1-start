<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

abstract class AbstractEvent
{
    protected static string $name = 'rkwadriga.jwt.abstract_event';

    public static function getName(): string
    {
        return static::$name;
    }
}