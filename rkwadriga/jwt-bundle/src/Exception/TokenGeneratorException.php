<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class TokenGeneratorException extends BaseTokenException
{
    public const INVALID_PAYLOAD         = 7831213564;
    public const INVALID_TOKEN_TYPE      = 7514021054;
    public const INVALID_ALGORITHM       = 7802348710;
}