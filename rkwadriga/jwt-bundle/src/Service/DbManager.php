<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\DbManagerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\Enum\TokenRefreshingContext;
use Rkwadriga\JwtBundle\Service\Db\CreateTableTrait;
use Rkwadriga\JwtBundle\Service\Db\ReadQueriesTrait;
use Rkwadriga\JwtBundle\Service\Db\WriteQueriesTrait;

class DbManager implements DbManagerInterface
{
    use CreateTableTrait;
    use ReadQueriesTrait;
    use WriteQueriesTrait;

    public function writeRefreshToken(TokenInterface $refreshToken, TokenRefreshingContext $refreshingContext): void
    {
        dd($refreshToken);
    }

    public function isRefreshTokenExist(TokenInterface $refreshToken): bool
    {
        dd($refreshToken);
    }

    public function updateRefreshToken(TokenInterface $oldRefreshToken, TokenInterface $newRefreshToken): void
    {
        dd($oldRefreshToken, $newRefreshToken);
    }

}