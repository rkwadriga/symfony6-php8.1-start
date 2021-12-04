<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators\LoginAuthenticator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class RkwadrigaJwtExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(LoginAuthenticator::LOGIN_URL_CONFIG_KEY, $config['login_url']);
        $container->setParameter(LoginAuthenticator::LOGIN_PARAM_CONFIG_KEY, $config['login_pram']);
        $container->setParameter(LoginAuthenticator::PASSWORD_PARAM_CONFIG_KEY, $config['password_param']);
    }
}