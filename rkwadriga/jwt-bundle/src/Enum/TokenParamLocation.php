<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

enum TokenParamLocation: string
{
    use BackedEnumTrait;

    case HEADER = 'header';
    case URI = 'uri';
    case BODY = 'body';
}