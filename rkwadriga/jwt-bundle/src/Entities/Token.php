<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entities;

use DateTime;

class Token
{
    public const ACCESS = 'access_token';
    public const REFRESH = 'refresh_token';

    public function __construct(
        private string $access,
        private string $refresh,
        private DateTime $expiredAt
    ) {}

    public function getAccessToken(): string
    {
        return $this->access;
    }

    public function getRefreshToken(): string
    {
        return $this->refresh;
    }

    public function getExpiredAt(): DateTime
    {
        return $this->expiredAt;
    }


}