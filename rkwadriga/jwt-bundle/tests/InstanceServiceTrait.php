<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\DbManager;

trait InstanceServiceTrait
{
    protected function getConfigService(): Config
    {
        return new Config($this->container);
    }

    protected function getDbManager(?Config $configService = null): DbManager
    {
        return new DbManager(
            $configService ?? $this->getConfigService(),
            $this->container->get('doctrine.orm.entity_manager')
        );
    }
}