<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle;

use Rkwadriga\JwtBundle\DependencyInjection\RkwadrigaJwtExtension;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationStartedEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingStartedEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenRefreshingStartedEvent;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class RkwadrigaJwtBundle extends Bundle
{
    public function build( $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddEventAliasesPass([
            AuthenticationStartedEvent::class => AuthenticationStartedEvent::getName(),
            AuthenticationFinishedSuccessfulEvent::class => AuthenticationFinishedSuccessfulEvent::getName(),
            AuthenticationFinishedUnsuccessfulEvent::class => AuthenticationFinishedUnsuccessfulEvent::getName(),
            TokenCreatingStartedEvent::class => TokenCreatingStartedEvent::getName(),
            TokenCreatingFinishedSuccessfulEvent::class => TokenCreatingFinishedSuccessfulEvent::getName(),
            TokenCreatingFinishedUnsuccessfulEvent::class => TokenCreatingFinishedUnsuccessfulEvent::getName(),
            TokenRefreshingStartedEvent::class => TokenRefreshingStartedEvent::getName(),
            TokenRefreshingFinishedSuccessfulEvent::class => TokenRefreshingFinishedSuccessfulEvent::getName(),
            TokenRefreshingFinishedUnsuccessfulEvent::class => TokenRefreshingFinishedUnsuccessfulEvent::getName(),
        ]));

        $container->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new RkwadrigaJwtExtension();
    }
}