<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;

class HeadGenerator implements HeadGeneratorInterface
{
    public const TOKEN_TYPE = 'JWT';

    public function __construct(
        private Config $config
    ) {}

    public function generate(array $payload, TokenType $type, ?Algorithm $algorithm = null): array
    {
        return [
            'alg' => $algorithm?->value ?: $this->config->get(ConfigurationParam::ENCODING_ALGORITHM),
            'typ' => self::TOKEN_TYPE,
            'sub' => $type->value
        ];
    }
}