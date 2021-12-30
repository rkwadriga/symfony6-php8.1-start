<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use DateTimeInterface;

interface TokenInterface
{
    public function getType(): TokenType;

    public function getToken(): string;

    public function getCreatedAt(): DateTimeInterface;

    public function getExpiredAt(): DateTimeInterface;

    public function getHead(): array;

    public function getPayload(): array;

    public function getSignature(): string;

    public function getCalculatedSignature(): ?string;
}