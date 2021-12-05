<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exceptions;

class TokenException extends BaseException
{
    public const INVALID_TOKEN_FORMAT   = 9002304891;
}