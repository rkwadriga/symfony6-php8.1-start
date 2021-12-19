<?php
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\Enum\TokenRefreshingContext;

interface DbManagerInterface
{
    public function writeRefreshToken(TokenInterface $refreshToken, string|int $userID, TokenRefreshingContext $refreshingContext): void;

    public function isRefreshTokenExist(TokenInterface $refreshToken): bool;

    public function updateRefreshToken(TokenInterface $oldRefreshToken, TokenInterface $newRefreshToken): void;
}