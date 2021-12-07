<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

abstract class AbstractEvent
{
    public const NAME = 'rkwadriga_jwt_abstract_event';

    public static function getName(): string
    {
        return static::NAME;
    }
}