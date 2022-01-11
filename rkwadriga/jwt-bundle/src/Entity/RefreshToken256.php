<?php

namespace Rkwadriga\JwtBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Rkwadriga\JwtBundle\Repository\RefreshToken256Repository;

use DateTimeImmutable;

/**
 * @ORM\Entity(repositoryClass=RefreshToken256Repository::class)
 */
class RefreshToken256 implements RefreshTokenEntityInterface
{
    public function __construct(
        /**
         * @ORM\Id
         * @ORM\Column(type="string", length=64)
         */
        private string $userId,

        /**
         * @ORM\Id
         * @ORM\Column(type="string", length=64)
         */
        private string $refreshToken,

        /**
         * @ORM\Column(type="datetime_immutable")
         */
        private DateTimeImmutable $createdAt
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
