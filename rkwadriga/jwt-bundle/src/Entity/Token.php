<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;

class Token implements TokenInterface
{
    public function __construct(
        private string $token,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $expiredAt
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiredAt(): DateTimeImmutable
    {
        return $this->expiredAt;
    }
}