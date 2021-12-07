<?php

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\Repository\RereshTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RereshTokenRepository::class)
 */
class RefreshToken
{
    /**
     * @ORM\Column(type="string", length=64)
     */
    private ?int $userId = null;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private string $refreshToken;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $createdAt;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
