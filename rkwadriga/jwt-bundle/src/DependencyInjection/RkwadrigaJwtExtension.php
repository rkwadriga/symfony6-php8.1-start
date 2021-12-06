<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenIdentifier;
use Rkwadriga\JwtBundle\Exceptions\ConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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

        if (!in_array($config['access_token_location'], TokenIdentifier::$allowedLocations)) {
            $allowedLocations = implode(', ', TokenIdentifier::$allowedLocations);
            throw new ConfigurationException(
                "Invalid access token location: \"{$config['access_token_location']}\", allowed location: {$allowedLocations}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }
        if (!in_array($config['refresh_token_location'], TokenIdentifier::$allowedLocations)) {
            $allowedLocations = implode(', ', TokenIdentifier::$allowedLocations);
            throw new ConfigurationException(
                "Invalid refresh token location: \"{$config['refresh_token_location']}\", allowed location: {$allowedLocations}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }
        if (!in_array($config['token_type'], TokenIdentifier::$allowedTypes)) {
            $allowedTypes = implode(', ', TokenIdentifier::$allowedTypes);
            throw new ConfigurationException(
                "Invalid token type: \"{$config['token_type']}\", allowed types: {$allowedTypes}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }

        $container->setParameter('rkwadriga.jwt._login_url', $config['login_url']);
        $container->setParameter('rkwadriga.jwt.refresh_url', $config['refresh_url']);
        $container->setParameter('rkwadriga.jwt.login_pram', $config['login_pram']);
        $container->setParameter('rkwadriga.jwt.password_param', $config['password_param']);
        $container->setParameter('rkwadriga.jwt.secret_key', $config['secret_key']);
        $container->setParameter('rkwadriga.jwt.encoding_algorithm', $config['encoding_algorithm']);
        $container->setParameter('rkwadriga.jwt.access_token_life_time', $config['access_token_life_time']);
        $container->setParameter('rkwadriga.jwt.refresh_token_life_time', $config['refresh_token_life_time']);
        $container->setParameter('rkwadriga.jwt.access_token_location', $config['access_token_location']);
        $container->setParameter('rkwadriga.jwt.access_token_param_name', $config['access_token_param_name']);
        $container->setParameter('rkwadriga.jwt.refresh_token_location', $config['refresh_token_location']);
        $container->setParameter('rkwadriga.jwt.refresh_token_param_name', $config['refresh_token_param_name']);
        $container->setParameter('rkwadriga.jwt.token_type', $config['token_type']);
    }
}