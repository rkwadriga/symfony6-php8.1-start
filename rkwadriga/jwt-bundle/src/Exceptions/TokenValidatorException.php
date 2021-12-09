<?php declare(strict_types=1);
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exceptions;

class TokenValidatorException extends BaseTokenException
{
    public const ACCESS_TOKEN_EXPIRED    = 5692001476;
    public const REFRESH_TOKEN_EXPIRED   = 5703604489;
    public const INVALID_ACCESS_TOKEN    = 2358011563;
    public const INVALID_REFRESH_TOKEN   = 6981368745;
}