<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;

class HeadGenerator implements HeadGeneratorInterface
{
    private const TOKEN_TYPE = 'JWT';

    public function generate(array $payload, TokenType $type, Algorithm $algorithm): array
    {
        return [
            'alg' => $algorithm->value,
            'typ' => self::TOKEN_TYPE,
            'sub' => $type->value
        ];
    }
}