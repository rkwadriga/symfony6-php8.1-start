<?php
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

use Rkwadriga\JwtBundle\Exception\TokenException;

trait FindByValueEnumTrait
{
    public static function getByValue(string $value): self
    {
        $algorithm = null;
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                $algorithm = $case;
            }
        }
        if ($algorithm === null) {
            throw new TokenException("Invalid token param value: \"{$value}\"", TokenException::INVALID_TOKEN_PARAM_VALUE);
        }

        return $algorithm;
    }
}