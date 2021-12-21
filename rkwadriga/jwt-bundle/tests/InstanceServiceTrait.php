<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\SerializerInterface;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\DbManager;
use Rkwadriga\JwtBundle\Service\HeadGenerator;
use Rkwadriga\JwtBundle\Service\PayloadGenerator;
use Rkwadriga\JwtBundle\Service\Serializer;
use Rkwadriga\JwtBundle\Service\TokenGenerator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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

    protected function createTokenGeneratorInstance(
        ?Config $configService = null,
        ?SerializerInterface $serializer = null,
        ?HeadGeneratorInterface $headGenerator = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ): TokenGenerator {
        $configService = $configService ?? $this->createConfigServiceInstance();

        return new TokenGenerator(
            $configService,
            $eventDispatcher ?? $this->createMock(EventDispatcher::class),
            $serializer ?? $this->createSerializerInstance($configService),
            $headGenerator ?? $this->createHeadGeneratorInstance($configService)
        );
    }
}