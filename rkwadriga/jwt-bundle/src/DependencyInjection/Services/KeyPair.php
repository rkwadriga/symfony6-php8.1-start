<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Helpers\FileSystemHelper;

class KeyPair
{
    public function __construct(
        private string $private,
        private string $public,
        private string $keysDir,
        private string $privateKeyName,
        private string $publicKeyName
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
}