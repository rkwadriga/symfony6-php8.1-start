<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class SerializerException extends BaseException
{
    public const INVALID_BASE64_DATA = 3660576150;
    public const INVALID_JSON_DATA   = 3012589621;
}