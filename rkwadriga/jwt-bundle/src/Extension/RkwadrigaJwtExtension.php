<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Extension;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
use Rkwadriga\JwtBundle\Exception\ConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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
        $this->checkConfig($config);

        // Set user provider
        // rkwadriga_jwt_default_user_provider
        //[$userProviderConfigID, $userProviderConfigShortID] = [ConfigurationParam::PROVIDER->value(), ConfigurationParam::PROVIDER->shortValue()];
        $container->setAlias(ConfigurationParam::PROVIDER->value(), 'security.user.provider.concrete.' . $config[ConfigurationParam::PROVIDER->shortValue()]);
        foreach (ConfigurationParam::cases() as $case) {
            $container->setParameter($case->value(), $config[$case->shortValue()]);
        }
    }

    private function checkConfig(array $config): void
    {
        $tokenLocations = TokenParamLocation::values();
        $tokenTypes = TokenParamType::values();

        $accessTokenLocationKey = ConfigurationParam::ACCESS_TOKEN_LOCATION->shortValue();
        if (!in_array($config[$accessTokenLocationKey], $tokenLocations)) {
            $allowedLocations = implode(', ', $tokenLocations);
            throw new ConfigurationException(
                "Invalid access token location: \"{$config[$accessTokenLocationKey]}\", allowed location: {$allowedLocations}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }
        $refreshTokenLocationKey = ConfigurationParam::REFRESH_TOKEN_LOCATION->shortValue();
        if (!in_array($config[$refreshTokenLocationKey], $tokenLocations)) {
            $allowedLocations = implode(', ', $tokenLocations);
            throw new ConfigurationException(
                "Invalid refresh token location: \"{$config[$refreshTokenLocationKey]}\", allowed location: {$allowedLocations}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }
        $tokenTypeKey = ConfigurationParam::TOKEN_TYPE->shortValue();
        if (!in_array($config[$tokenTypeKey], $tokenTypes)) {
            $allowedTypes = implode(', ', $tokenTypes);
            throw new ConfigurationException(
                "Invalid token type: \"{$config[$tokenTypeKey]}\", allowed types: {$allowedTypes}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }
    }
}