<?php declare(strict_types=1);
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entities;

use DateTimeInterface;

interface TokenValidatableInterface
{
    public function getType(): ?string;

    public function getCreatedAt(): ?DateTimeInterface;

    public function getExpiredAt(): ?DateTimeInterface;

    public function getPayload(): array;
}