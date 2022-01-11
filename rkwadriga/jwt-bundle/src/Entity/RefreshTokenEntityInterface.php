<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeImmutable;

interface RefreshTokenEntityInterface
{
    public function getUserId(): string;

    public function setRefreshToken(string $refreshToken): self;

    public function getRefreshToken(): string;

    public function setCreatedAt(DateTimeImmutable $createdAt): self;

    public function getCreatedAt(): DateTimeImmutable;
}