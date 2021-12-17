<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\Enum\FindByValueEnumTrait;

enum Algorithm: string
{
    use FindByValueEnumTrait;

    case SHA256 = 'SHA256';
    case SHA512 = 'SHA512';
}