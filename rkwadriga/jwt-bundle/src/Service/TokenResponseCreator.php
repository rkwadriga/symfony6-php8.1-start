<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenResponseCreatorInterface;

class TokenResponseCreator implements TokenResponseCreatorInterface
{
    public function create(TokenInterface $accessToken, TokenInterface $refreshToken): array
    {
        return [
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $refreshToken->getToken(),
            'expiredAt' => $accessToken->getExpiredAt(),
        ];
    }
}