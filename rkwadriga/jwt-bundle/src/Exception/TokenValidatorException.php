<?php declare(strict_types=1);
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class TokenValidatorException extends BaseTokenException
{
    public const ACCESS_TOKEN_EXPIRED    = 5692001476;
    public const REFRESH_TOKEN_EXPIRED   = 5703604489;
    public const INVALID_ACCESS_TOKEN    = 5358011563;
    public const INVALID_REFRESH_TOKEN   = 6981368745;
    public const INVALID_TYPE            = 6325698751;
    public const INVALID_SIGNATURE       = 6320145796;
    public const INVALID_FORMAT          = 6018046145;
}