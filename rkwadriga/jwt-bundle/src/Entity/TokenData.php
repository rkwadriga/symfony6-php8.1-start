<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeImmutable;

class TokenData implements TokenValidatableInterface
{
    private ?string $alg = null;
    private ?string $typ = null;
    private ?string $sub = null;
    private ?int $timestamp = null;
    private ?int $exp = null;
    private array $payload;

    public function __construct(
        private string $token,
        array $data
    ) {
        foreach ($data as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
                unset($data[$name]);
            }
        }
        $this->payload = $data;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getAlgorithm(): ?string
    {
        return $this->alg;
    }

    public function getType(): ?string
    {
        return $this->sub;
    }

    public function getDataType(): ?string
    {
        return $this->typ;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        if ($this->timestamp === null) {
            return null;
        }
        $time = new DateTimeImmutable();
        return $time->setTimestamp($this->timestamp);
    }

    public function getExpiredAt(): ?DateTimeImmutable
    {
        if ($this->exp === null) {
            return null;
        }
        $time = new DateTimeImmutable();
        return $time->setTimestamp($this->exp);
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}