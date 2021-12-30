<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

enum Algorithm: string
{
    case SHA256 = 'SHA256';
    case SHA512 = 'SHA512';
}