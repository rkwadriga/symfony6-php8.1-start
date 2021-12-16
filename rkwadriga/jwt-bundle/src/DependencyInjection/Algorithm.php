<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\Exception\TokenGeneratorException;

enum Algorithm: string
{
    case SHA256 = 'SHA256';
    case SHA512 = 'SHA512';

    public static function getByValue(string $value): self
    {
        $algorithm = null;
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                $algorithm = $case;
            }
        }
        if ($algorithm === null) {
            throw new TokenGeneratorException("Invalid algorithm: \"{$value}\"", TokenGeneratorException::INVALID_ALGORITHM);
        }

        return $algorithm;
    }
}