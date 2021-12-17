<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\Exception\TokenValidatorException;

interface TokenGeneratorInterface
{
    public function fromPayload(array $payload, TokenType $type, ?Algorithm $algorithm = null): TokenInterface;

    /**
     * @throws TokenValidatorException
     */
    public function fromString(string $token, TokenType $type): TokenInterface;
}