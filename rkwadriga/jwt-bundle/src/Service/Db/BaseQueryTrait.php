<?php declare(strict_types=1);
/**
 * Created 2021-12-08
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service\Db;

use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Entity\RefreshToken256;
use Rkwadriga\JwtBundle\Entity\RefreshToken512;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;

/**
 * @property EntityManagerInterface $em
 */
trait BaseQueryTrait
{
    private ?Algorithm $algorithm = null;
    private ?string $tableName = null;

    private function setTableName(): void
    {
        $metadata = $this->em->getMetadataFactory()->getMetadataFor($this->getEntityClass());
        $metadata->setPrimaryTable(['name' => $this->getTableName()]);
    }

    private function getTableName(): string
    {
        if ($this->tableName !== null) {
            return $this->tableName;
        }

        return $this->tableName = $this->config->get(ConfigurationParam::REFRESH_TOKEN_TABLE)
          . ($this->getAlgorithm() === Algorithm::SHA256 ? '_256' : '_512');
    }

    private function getAlgorithm(): Algorithm
    {
        if ($this->algorithm !== null) {
            return $this->algorithm;
        }

        return $this->algorithm = Algorithm::from($this->config->get(ConfigurationParam::ENCODING_ALGORITHM));
    }

    private function getEntityClass(): string
    {
        return $this->getAlgorithm() === Algorithm::SHA256 ? RefreshToken256::class : RefreshToken512::class;
    }
}