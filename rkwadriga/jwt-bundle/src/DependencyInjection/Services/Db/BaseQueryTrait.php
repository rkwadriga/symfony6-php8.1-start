<?php declare(strict_types=1);
/**
 * Created 2021-12-08
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services\Db;

use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\Entity\RefreshToken;

/**
 * @property EntityManagerInterface $em
 * @property string $table
 */
trait BaseQueryTrait
{
    private function setTableName(): void
    {
        $metadata = $this->em->getMetadataFactory()->getMetadataFor(RefreshToken::class);
        $metadata->setPrimaryTable(['name' => $this->table]);
    }
}