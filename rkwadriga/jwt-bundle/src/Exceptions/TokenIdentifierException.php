<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exceptions;

class TokenIdentifierException extends BaseException
{
    public const TOKEN_NOT_FOUND = 3025698400;
    public const INVALID_TOKEN   = 6566106736;
}