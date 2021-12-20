<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Entity\RefreshToken256;
use Rkwadriga\JwtBundle\Entity\RefreshToken512;
use Rkwadriga\JwtBundle\Entity\RefreshTokenEntityInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;

trait RefreshTokenTrait
{
    protected function getRefreshTokenTableName(Algorithm|string $algorithm): string
    {
        if (is_string($algorithm)) {
            $algorithm = Algorithm::getByValue($algorithm);
        }

        return $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_TABLE) . ($algorithm === Algorithm::SHA256 ? '_256' : '_512');
    }

    protected function getRefreshTokenEntityClass(Algorithm|string $algorithm): string
    {
        if (is_string($algorithm)) {
            $algorithm = Algorithm::getByValue($algorithm);
        }

        return $algorithm === Algorithm::SHA256 ? RefreshToken256::class : RefreshToken512::class;
    }

    protected function findRefreshTokenBy(Algorithm|string $algorithm, array $condition): ?RefreshTokenEntityInterface
    {
        return $this->entityManager->getRepository($this->getRefreshTokenEntityClass($algorithm))->findOneBy($condition);
    }
}