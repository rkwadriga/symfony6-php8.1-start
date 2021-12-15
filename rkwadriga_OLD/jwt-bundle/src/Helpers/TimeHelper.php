<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Helpers;

use DateTime;
use DateInterval;

class TimeHelper
{
    public static function addSeconds(int $seconds, ?DateTime $time = null): DateTime
    {
        if ($time === null) {
            $time = new DateTime();
        }
        return $time->add(DateInterval::createFromDateString($seconds . ' seconds'));
    }

    public static function fromTimeStamp(int $timestamp): DateTime
    {
        $time = new DateTime();
        $time->setTimestamp($timestamp);
        return $time;
    }
}