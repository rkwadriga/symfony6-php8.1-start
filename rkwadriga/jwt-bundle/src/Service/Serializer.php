<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

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
            throw new SerializerException("Invalid data \"{$data}\"", SerializerException::INVALID_BASE64_DATA);
        }

        return $decoded;
    }

    public function serialize(array $data): string
    {
        return $this->encode(json_encode($data));
    }

    public function signature(string $data, ?Algorithm $algorithm = null): string
    {
        $algo = $algorithm?->value ?: $this->config->get(ConfigurationParam::ENCODING_ALGORITHM);
        $secret = $this->config->get(ConfigurationParam::SECRET_KEY);

        return $this->encode(hash_hmac($algo, $data, $secret));
    }

    public function deserialiaze(string $data): array
    {
        $jsonData = $this->decode($data);
        if (($deserialized = json_decode($jsonData, true)) === null) {
            $message = "Invalid data \"{$jsonData}\"";
            if ($jsonLastError = json_last_error_msg()) {
                $message .= ". Error: {$jsonLastError}";
            }
            throw new SerializerException($message, SerializerException::INVALID_JSON_DATA);
        }

        return $deserialized;
    }

    public function implode(array $data): string
    {
        return implode('.', $data);
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