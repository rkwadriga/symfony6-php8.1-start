<?php
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

use BackedEnum;

trait BackedEnumTrait
{
    public static function values(): array
    {
        return array_map(function (BackedEnum $enumItem) {
            return $enumItem->value;
        }, self::cases());
    }
}