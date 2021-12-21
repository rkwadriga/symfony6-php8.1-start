<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\DbManager;
use Rkwadriga\JwtBundle\Service\HeadGenerator;
use Rkwadriga\JwtBundle\Service\PayloadGenerator;
use Rkwadriga\JwtBundle\Service\Serializer;

trait InstanceServiceTrait
{
    protected function createConfigServiceInstance(): Config
    {
        return new Config($this->container);
    }

    protected function createDbManagerInstance(?Config $configService = null): DbManager
    {
        return new DbManager(
            $configService ?? $this->createConfigServiceInstance(),
            $this->container->get('doctrine.orm.entity_manager')
        );
    }

    protected function createHeadGeneratorInstance(?Config $configService = null): HeadGenerator
    {
        return new HeadGenerator($configService ?? $this->createConfigServiceInstance());
    }

    protected function createPayloadGeneratorInstance(): PayloadGenerator
    {
        return new PayloadGenerator();
    }

    protected function createSerializerInstance(?Config $configService = null): Serializer
    {
        return new Serializer($configService ?? $this->createConfigServiceInstance());
    }
}