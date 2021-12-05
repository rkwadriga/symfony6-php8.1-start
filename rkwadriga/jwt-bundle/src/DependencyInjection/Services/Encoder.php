<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Entities\KeyPair;
use Rkwadriga\JwtBundle\Exceptions\EncoderException;

class Encoder
{
    private ?KeyPair $keyPair = null;

    public function __construct(
        private KeyGenerator $keyGenerator
    ) {}

    public function encode(string $decoded): string
    {
        $keyPair = $this->getKeyPair();
        $baseHash = hash($keyPair->getAlgorithm(), $decoded);
        $publicKeyResource = $this->keyGenerator->getPublicKeyResource($keyPair->getPublic());

        if (!openssl_public_encrypt($baseHash, $encrypted, $publicKeyResource)) {
            $message = 'Ca encrypt the data. ';
            if ($openSslError = openssl_error_string()) {
                $message .= "Error: {$openSslError}";
            } else {
                $message .= 'Encryption Error';
            }
            throw new EncoderException($message, EncoderException::ENCRYPTION_ERROR);
        }

        return $encrypted;
    }

    public function decode(string $encoded): string
    {
        $keyPair = $this->getKeyPair();
        $privateKey = $this->keyGenerator->getPrivateKeyResource($keyPair->getPrivate());
        if (!openssl_private_decrypt($encoded, $decrypted, $privateKey)) {
            $message = 'Ca not decrypt the data. ';
            if ($openSslError = openssl_error_string()) {
                $message .= "Error: {$openSslError}";
            } else {
                $message .= 'Decryption Error';
            }
            throw new EncoderException($message, EncoderException::DECRYPTION_ERROR);
        }

        return $decrypted;
    }

    private function getKeyPair(): KeyPair
    {
        if ($this->keyPair !== null) {
            return $this->keyPair;
        }
        $keyPair = $this->keyGenerator->getKeyPair();
        if ($keyPair === null) {
            throw new EncoderException(
                sprintf('Secret key not found in directory %s', $this->keyGenerator->getKeysDir()),
                EncoderException::PRIVATE_KEY_NOF_FOUND
            );
        }

        return $this->keyPair = $keyPair;
    }
}