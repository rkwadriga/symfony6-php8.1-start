<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\SerializerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Exception\SerializerException;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;

class Serializer implements SerializerInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function encode(string $data): string
    {
        return str_replace('=', '', base64_encode($data));
    }

    public function decode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        if (($decoded = base64_decode($data)) === false || $decoded === '') {
            throw new SerializerException("Invalid base64-string", SerializerException::INVALID_BASE64_DATA);
        }

        return $decoded;
    }

    public function serialize(array $data): string
    {
        return $this->encode(json_encode($data));
    }

    public function deserialiaze(string $data): array
    {
        $jsonData = $this->decode($data);
        if (($deserialized = json_decode($jsonData, true)) === null) {
            $message = 'Invalid json';
            if ($jsonLastError = json_last_error_msg()) {
                $message .= ". Error: {$jsonLastError}";
            }
            throw new SerializerException($message, SerializerException::INVALID_JSON_DATA);
        }

        return $deserialized;
    }

    public function signature(string $data, ?Algorithm $algorithm = null): string
    {
        $algo = $algorithm?->value ?: $this->config->get(ConfigurationParam::ENCODING_ALGORITHM);
        $secret = $this->config->get(ConfigurationParam::SECRET_KEY);
        $encodingHashingCount = $this->config->get(ConfigurationParam::ENCODING_HASHING_COUNT);

        $result = hash_hmac($algo, $data, $secret);
        for ($i = 1; $i < $encodingHashingCount; $i++) {
            $result = hash_hmac($algo, $i . $result, $secret . ':' . $i);
        }

        return $result;
    }

    public function implode(string ...$parts): string
    {
        return implode('.', $parts);
    }

    public function explode(string $data): array
    {
        $result = explode('.', $data);
        if (count($result) !== 3) {
            throw new TokenValidatorException('Invalid token format', TokenValidatorException::INVALID_FORMAT);
        }

        return $result;
    }
}