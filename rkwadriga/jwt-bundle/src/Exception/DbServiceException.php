<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class DbServiceException extends BaseException
{
    public const CAN_NOT_CREATE_TABLE   = 4756361204;
    public const SQL_ERROR              = 4820123745;
    public const TOKENS_COUNT_EXCEEDED  = 4565320076;
    public const REFRESH_TOKEN_MISSED   = 5230815335;
}