<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

enum TokenParamType: string
{
    use BackedEnumTrait;

    case BEARER = 'Bearer';
    case SIMPLE = 'Simple';
}