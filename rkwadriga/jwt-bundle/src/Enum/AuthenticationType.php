<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

enum AuthenticationType:string
{
    case JWT = 'jwt_auth';
    case REFRESH = 'refresh_auth';
    case LOGIN = 'login_auth';
}