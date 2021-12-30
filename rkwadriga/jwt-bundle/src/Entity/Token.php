<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;

class Token implements TokenInterface
{
    public function __construct(
        private TokenType $type,
        private string $token,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $expiredAt,
        private array $head,
        private array $payload,
        private string $signature,
        private ?string $calculatedSignature = null
    ) {}

    public function getType(): TokenType
    {
        return $this->type;
    }

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

    public function getHead(): array
    {
        return $this->head;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getCalculatedSignature(): ?string
    {
        return $this->calculatedSignature;
    }
}