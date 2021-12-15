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
use Rkwadriga\JwtBundle\Helpers\EnumHelper;
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

        $tokenLocations = TokenParamLocation::values();
        $tokenTypes = TokenParamType::values();

        if (!in_array($config['access_token_location'], $tokenLocations)) {
            $allowedLocations = implode(', ', $tokenLocations);
            throw new ConfigurationException(
                "Invalid access token location: \"{$config['access_token_location']}\", allowed location: {$allowedLocations}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }
        if (!in_array($config['refresh_token_location'], $tokenLocations)) {
            $allowedLocations = implode(', ', $tokenLocations);
            throw new ConfigurationException(
                "Invalid refresh token location: \"{$config['refresh_token_location']}\", allowed location: {$allowedLocations}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }
        if (!in_array($config['token_type'], $tokenTypes)) {
            $allowedTypes = implode(', ', $tokenTypes);
            throw new ConfigurationException(
                "Invalid token type: \"{$config['token_type']}\", allowed types: {$allowedTypes}",
                ConfigurationException::INVALID_PARAM_VALUE
            );
        }

        $container->setAlias(ConfigurationParam::PROVIDER->value(), 'security.user.provider.concrete.' . $config[ConfigurationParam::PROVIDER->shortValue()]);
        $container->setParameter(ConfigurationParam::LOGIN_URL->value(), $config[ConfigurationParam::LOGIN_URL->shortValue()]);
        $container->setParameter(ConfigurationParam::REFRESH_URL->value(), $config[ConfigurationParam::REFRESH_URL->shortValue()]);
        $container->setParameter(ConfigurationParam::LOGIN_PARAM->value(), $config[ConfigurationParam::LOGIN_PARAM->shortValue()]);
        $container->setParameter(ConfigurationParam::PASSWORD_PARAM->value(), $config[ConfigurationParam::PASSWORD_PARAM->shortValue()]);
        $container->setParameter(ConfigurationParam::SECRET_KEY->value(), $config[ConfigurationParam::SECRET_KEY->shortValue()]);
        $container->setParameter(ConfigurationParam::ENCODING_ALGORITHM->value(), $config[ConfigurationParam::ENCODING_ALGORITHM->shortValue()]);
        $container->setParameter(ConfigurationParam::ENCODING_HASHING_COUNT->value(), $config[ConfigurationParam::ENCODING_HASHING_COUNT->shortValue()]);
        $container->setParameter(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME->value(), $config[ConfigurationParam::ACCESS_TOKEN_LIFE_TIME->shortValue()]);
        $container->setParameter(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME->value(), $config[ConfigurationParam::REFRESH_TOKEN_LIFE_TIME->shortValue()]);
        $container->setParameter(ConfigurationParam::ACCESS_TOKEN_LOCATION->value(), $config[ConfigurationParam::ACCESS_TOKEN_LOCATION->shortValue()]);
        $container->setParameter(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME->value(), $config[ConfigurationParam::ACCESS_TOKEN_PARAM_NAME->shortValue()]);
        $container->setParameter(ConfigurationParam::REFRESH_TOKEN_LOCATION->value(), $config[ConfigurationParam::REFRESH_TOKEN_LOCATION->shortValue()]);
        $container->setParameter(ConfigurationParam::REFRESH_TOKEN_PARAM_NAME->value(), $config[ConfigurationParam::REFRESH_TOKEN_PARAM_NAME->shortValue()]);
        $container->setParameter(ConfigurationParam::TOKEN_TYPE->value(), $config[ConfigurationParam::TOKEN_TYPE->shortValue()]);
        $container->setParameter(ConfigurationParam::REFRESH_TOKEN_IN_DB->value(), $config[ConfigurationParam::REFRESH_TOKEN_IN_DB->shortValue()]);
        $container->setParameter(ConfigurationParam::REFRESH_TOKEN_TABLE->value(), $config[ConfigurationParam::REFRESH_TOKEN_TABLE->shortValue()]);
        $container->setParameter(ConfigurationParam::REFRESH_TOKENS_LIMIT->value(), $config[ConfigurationParam::REFRESH_TOKENS_LIMIT->shortValue()]);
        $container->setParameter(ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED->value(), $config[ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED->shortValue()]);
    }
}