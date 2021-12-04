<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle;

use Rkwadriga\JwtBundle\DependencyInjection\RkwadrigaJwtExtension;
use Rkwadriga\JwtBundle\DependencyInjection\Services\Generator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class RkwadrigaJwtBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new RkwadrigaJwtExtension();
    }
}