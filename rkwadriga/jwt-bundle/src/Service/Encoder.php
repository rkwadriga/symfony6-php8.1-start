<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\EncoderInterface;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;

class Encoder implements EncoderInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function encode(array $head, array $payload, Algorithm $algorithm): string
    {
        [$headString, $payloadString] = [json_encode($head), json_encode($payload)];
        $data = base64_encode($headString) . '.' . base64_encode($payloadString);
        $secret = $this->config->get(ConfigurationParam::SECRET_KEY);

        return hash_hmac($algorithm->value, $data, $secret);
    }
}