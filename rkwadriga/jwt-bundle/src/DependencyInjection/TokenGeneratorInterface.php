<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

interface TokenGeneratorInterface
{
    public function generate(array $payload, TokenType $type): TokenInterface;
}