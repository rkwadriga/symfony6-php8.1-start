<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class TokenIdentifierException extends BaseTokenException
{
    public const INVALID_ACCESS_TOKEN  = 6980114856;
    public const INVALID_REFRESH_TOKEN = 7065896123;
    public const ACCESS_TOKEN_MISSED   = 6705776321;
    public const REFRESH_TOKEN_MISSED  = 7800360156;
}