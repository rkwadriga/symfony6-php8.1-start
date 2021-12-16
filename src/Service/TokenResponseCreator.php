<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace App\Service;

use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenResponseCreatorInterface;

class TokenResponseCreator implements TokenResponseCreatorInterface
{
    public function create(TokenInterface $accessToken, TokenInterface $refreshToken): array
    {
        return [
            'token' => $accessToken->getToken()
        ];
    }

}