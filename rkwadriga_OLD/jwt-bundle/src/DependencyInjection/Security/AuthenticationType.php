<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security;

class AuthenticationType
{
    public const JWT = 'jwt_auth';
    public const REFRESH = 'refresh_auth';
    public const LOGIN = 'login_auth';
}