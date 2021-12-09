<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeImmutable;
use Symfony\Component\Security\Core\User\UserInterface;

class Token implements TokenInterface
{
    public function __construct(
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $expiredAt,
        private string $access,
        private ?string $refresh = null,
        private ?UserInterface $user = null
    ) {}

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiredAt(): DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function getAccessToken(): string
    {
        return $this->access;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refresh;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
}