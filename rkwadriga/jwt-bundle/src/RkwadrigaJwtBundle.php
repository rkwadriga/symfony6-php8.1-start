<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle;

use Rkwadriga\JwtBundle\DependencyInjection\RkwadrigaJwtExtension;
use Rkwadriga\JwtBundle\Event\AuthenticationStartedEvent;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class RkwadrigaJwtBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddEventAliasesPass([
            AuthenticationStartedEvent::class => AuthenticationStartedEvent::NAME,
        ]));

        $container->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new RkwadrigaJwtExtension();
    }
}