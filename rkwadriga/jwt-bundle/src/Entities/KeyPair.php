<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entities;

class KeyPair
{
    public function __construct(
        private string $private,
        private string $public,
        private string $keysDir,
        private string $privateKeyName,
        private string $publicKeyName,
        private string $algorithm,
        private int $keyLength,
        private int $keyType,
        private array $config
    ) {}

    public function getPrivate(): string
    {
        return $this->private;
    }

    public function getPublic(): string
    {
        return $this->public;
    }

    public function keysDir(): string
    {
        return $this->keysDir;
    }

    public function privateKeyPath(): string
    {
        return $this->keysDir() . DIRECTORY_SEPARATOR . $this->privateKeyName;
    }

    public function publicKeyPath(): string
    {
        return $this->keysDir() . DIRECTORY_SEPARATOR . $this->publicKeyName;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getKeyLength(): int
    {
        return $this->keyLength;
    }

    public function getKeyType(): int
    {
        return $this->keyType;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}