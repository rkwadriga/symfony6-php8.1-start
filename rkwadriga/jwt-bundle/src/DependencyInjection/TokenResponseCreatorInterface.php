<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

interface TokenResponseCreatorInterface
{
    public function create(TokenInterface $accessToken, TokenInterface $refreshToken): array;
}