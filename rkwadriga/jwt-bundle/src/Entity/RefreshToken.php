<?php

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RefreshTokenRepository::class)
 */
class RefreshToken
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

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
