<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exceptions;

abstract class EncryptionException extends BaseException
{
    public const OPEN_SSL_ERROR_CODE = 6532197531;
}