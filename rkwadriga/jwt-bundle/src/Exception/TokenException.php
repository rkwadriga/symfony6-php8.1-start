<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class TokenException extends BaseTokenException
{
    public const INVALID_TOKEN_FORMAT        = 9002304891;
    public const INVALID_TOKEN_PARAM_VALUE   = 9324058604;
}