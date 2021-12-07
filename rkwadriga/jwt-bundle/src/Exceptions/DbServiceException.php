<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exceptions;

class DbServiceException extends BaseException
{
    public const TABLE_DOES_NOT_EXIST  = 4365079032;
    public const CAN_NOT_CREATE_TABLE  = 4756361204;
}