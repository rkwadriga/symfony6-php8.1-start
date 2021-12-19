<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class TokenGeneratorException extends BaseTokenException
{
    public const INVALID_PAYLOAD   = 7831213564;
    public const INVALID_ALGORITHM = 7514021054;
}