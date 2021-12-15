<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Exception\EncoderException;

class Encoder
{
    public function __construct(
        private string $algorithm,
        private string $secretKey
    ) {}

    public function encode(string $decoded): string
    {
        return hash_hmac($this->algorithm, $decoded, $this->secretKey);
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}