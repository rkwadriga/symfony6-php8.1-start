<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;

class TokenGenerator implements TokenGeneratorInterface
{
    public function __construct(
        private Config $config,
        private HeadGeneratorInterface $headGenerator
    ) {}

    public function generate(array $payload, TokenType $type): TokenInterface
    {
        $algorithm = Algorithm::getByValue($this->config->get(ConfigurationParam::ENCODING_ALGORITHM));
        $head = $this->headGenerator->generate($payload, $type, $algorithm);
        dd($head, $payload, $type);
    }
}