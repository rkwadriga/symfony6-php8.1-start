<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

enum TokenRefreshingContext: string
{
    use BackedEnumTrait;

    case LOGIN = 'login';
    case REFRESH = 'refresh';
}