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
        return hash_hmac($keyPair->getAlgorithm(), $decoded, $keyPair->getPrivate());
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