<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class RkwadrigaJwtExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        /*$loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        dd($loader->load('security.yaml'));*/

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('rkwadriga_jwt_configuration_login_url', $config['login_url']);
        $container->setParameter('rkwadriga_jwt_configuration_login_pram', $config['login_pram']);
        $container->setParameter('rkwadriga_jwt_configuration_password_param', $config['password_param']);
    }
}