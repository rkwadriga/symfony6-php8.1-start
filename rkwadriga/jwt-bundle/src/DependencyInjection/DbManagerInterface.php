<?php
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\Entity\RefreshTokenEntityInterface;
use Rkwadriga\JwtBundle\Enum\TokenRefreshingContext;

interface DbManagerInterface
{
    public function writeRefreshToken(string|int $userID, TokenInterface $refreshToken, TokenRefreshingContext $refreshingContext): void;

    public function findRefreshToken(string|int $userID, TokenInterface $refreshToken): ?RefreshTokenEntityInterface;

    public function updateRefreshToken(string|int $userID, TokenInterface $oldRefreshToken, TokenInterface $newRefreshToken): void;
}