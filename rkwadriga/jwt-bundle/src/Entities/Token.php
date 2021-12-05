<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entities;

use DateTimeImmutable;

class Token
{
    public function __construct(
        private string $access,
        private string $refresh,
        private DateTimeImmutable $expiredAt
    ) {}
}