<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Entities\KeyPair;
use Rkwadriga\JwtBundle\Exceptions\KeyGeneratorException;
use Rkwadriga\JwtBundle\Helpers\FileSystemHelper;

class KeyGenerator
{
    private const DEFAULT_ALGORITHM = 'sha512';
    private const DEFAULT_LENGTH = 2048;
    private const DEFAULT_TYPE = OPENSSL_KEYTYPE_RSA;

    public function __construct(
        private string $keysDir,
        private string $privateKeyName,
        private string $publicKeyName,
        private ?string $algorithm = null,
        private ?int $keyLength = null,
        private ?int $keyType = null
    ) {
        if ($this->algorithm === null) {
            $this->algorithm = self::DEFAULT_ALGORITHM;
        }
        if ($this->keyLength === null) {
            $this->keyLength = self::DEFAULT_LENGTH;
        }
        if ($this->keyType === null) {
            $this->keyType = self::DEFAULT_TYPE;
        }
    }

    public function setKeysDir(string $keysDir)
    {
        $this->keysDir = $keysDir;
    }

    public function getKeysDir(): string
    {
        return FileSystemHelper::normalizePath($this->keysDir);
    }

    public function getPrivateKeyPath(): string
    {
        return $this->getKeysDir() . DIRECTORY_SEPARATOR . $this->privateKeyName;
    }

    public function getPublicKeyPath(): string
    {
        return $this->getKeysDir() . DIRECTORY_SEPARATOR . $this->publicKeyName;
    }

    public function generate(?string $algorithm = null, ?int $length = null, ?int $type = null): KeyPair
    {
        $errorMessage = 'Can not generate key pair. ';
        $defaultExplanation = 'Try to use another encrypt algorithm, another key type or key length';

        if ($algorithm !== null) {
            $this->algorithm = $algorithm;
        }
        if ($length !== null) {
            $this->keyLength = $length;
        }
        if ($type !== null) {
            $this->keyType = $type;
        }

        $openssl = openssl_pkey_new([
            'digest_alg' => $this->algorithm,
            'private_key_bits' => $this->keyLength,
            'private_key_type' => $this->keyType,
        ]);
        if ($openssl === false) {
            if ($error = openssl_error_string() ?: $php_errormsg) {
                $errorMessage .= 'Error: ' . $error;
            } else {
                $errorMessage .= $defaultExplanation;
            }
            throw new KeyGeneratorException($errorMessage, KeyGeneratorException::OPEN_SSL_ERROR_CODE);
        }

        if (!openssl_pkey_export($openssl, $private)) {
            if ($error = openssl_error_string() ?: $php_errormsg) {
                $errorMessage .= 'Error: ' . $error;
            } else {
                $errorMessage .= $defaultExplanation;
            }
            throw new KeyGeneratorException($errorMessage, KeyGeneratorException::OPEN_SSL_ERROR_CODE);
        }

        $public = openssl_pkey_get_details($openssl);
        if ($public === false) {
            if ($error = openssl_error_string() ?: $php_errormsg) {
                $errorMessage .= 'Error: ' . $error;
            } else {
                $errorMessage .= $defaultExplanation;
            }
            throw new KeyGeneratorException($errorMessage, KeyGeneratorException::OPEN_SSL_ERROR_CODE);
        }

        return $this->createKey($private, $public['key']);
    }

    private function createKey(string $private, string $public): KeyPair
    {
        return new KeyPair(
            $private,
            $public,
            $this->keysDir,
            $this->privateKeyName,
            $this->publicKeyName,
            $this->algorithm,
            $this->keyLength,
            $this->keyType
        );
    }
}