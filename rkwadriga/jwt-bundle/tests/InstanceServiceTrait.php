<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\DbManager;
use Rkwadriga\JwtBundle\Service\HeadGenerator;

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

    protected function getHeadGenerator(?Config $configService = null): HeadGeneratorInterface
    {
        return new HeadGenerator($configService ?? $this->getConfigService());
    }
}