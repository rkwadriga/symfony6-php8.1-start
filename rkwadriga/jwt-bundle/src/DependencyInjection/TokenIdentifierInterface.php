<?php
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Symfony\Component\HttpFoundation\Request;

interface TokenIdentifierInterface
{
    public function identify(Request $request, TokenType $tokenType): string;
}