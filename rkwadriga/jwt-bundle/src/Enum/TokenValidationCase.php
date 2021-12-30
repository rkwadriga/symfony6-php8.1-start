<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

enum TokenValidationCase
{
    case EXPIRED;
    case TYPE;
    case USER_IDENTIFIER;
    case TOKEN_PARAM_TYPE;
    case SIGNATURE;
}