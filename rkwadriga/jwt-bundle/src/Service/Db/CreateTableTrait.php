<?php
/**
 * Created 2021-12-08
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service\Db;

use Doctrine\ORM\Tools\ToolsException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\Exception\DbServiceException;
use Rkwadriga\JwtBundle\Service\Config;

/**
 * @property Config $config
 * @property EntityManagerInterface $em
 * @property string $table
 */
trait CreateTableTrait
{
    private function createTable(): void
    {
        try {
            // Try to create table with specific table name
            $schemaTool = new SchemaTool($this->em);
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($this->getEntityClass());
            $metadata->setPrimaryTable(['name' => $this->getTableName()]);
            $schemaTool->createSchema([$metadata]);
        } catch (\Exception $e) {
            if ($e instanceof ToolsException) {
                // Table already exist
                return;
            }

            throw new DbServiceException(
                sprintf('Can not create table "%s": %s', $this->getTableName(), $e->getMessage()),
                DbServiceException::CAN_NOT_CREATE_TABLE,
                $e
            );
        }
    }
}