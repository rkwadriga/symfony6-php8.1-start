<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Helpers\FileSystemHelper;

class KeyPair
{
    public const DEFAULT_DIR = 'config/jwt';
    public const PRIVATE_KEY_NAME = 'private.pem';
    public const PUBLIC_KEY_NAME = 'public.pem';

    public function __construct(
        private string $private,
        private string $public
    ) {}

    public function getPrivate(): string
    {
        return $this->private;
    }

    public function getPublic(): string
    {
        return $this->public;
    }

    public static function privateKeyPath(string $dirPath = self::DEFAULT_DIR): string
    {
        return FileSystemHelper::normalizePath($dirPath) . DIRECTORY_SEPARATOR . self::PRIVATE_KEY_NAME;
    }

    public static function publicKeyPath(string $dirPath = self::DEFAULT_DIR): string
    {
        return FileSystemHelper::normalizePath($dirPath) . DIRECTORY_SEPARATOR . self::PUBLIC_KEY_NAME;
    }
}