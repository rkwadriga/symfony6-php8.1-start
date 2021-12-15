<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exception;

class TokenRefresherException extends BaseTokenException
{
    public const NOT_FOUND            = 2053652352;
    public const INVALID_CREATED_AT   = 2049502054;
    public const SEARCHING_ERROR      = 3568910557;
    public const UPDATING_ERROR       = 2863104832;
}