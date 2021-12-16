<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

interface TokenGeneratorInterface
{
    public function fromPayload(array $payload, TokenType $type, ?Algorithm $algorithm = null): TokenInterface;

    public function fromString(string $token, TokenType $type): TokenInterface;
}