<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Extension;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $threeBuilder = new TreeBuilder('rkwadriga_jwt');
        $threeBuilder->getRootNode()
            ->children()
                ->scalarNode(ConfigurationParam::PROVIDER->shortValue())->defaultValue('rkwadriga_jwt_default_user_provider')->end()
                ->scalarNode(ConfigurationParam::LOGIN_URL->shortValue())->defaultValue('rkwadriga_jwt_auth_login')->end()
                ->scalarNode(ConfigurationParam::REFRESH_URL->shortValue())->defaultValue('rkwadriga_jwt_refresh_token')->end()
                ->scalarNode(ConfigurationParam::USER_IDENTIFIER->shortValue())->defaultValue('email')->end()
                ->scalarNode(ConfigurationParam::LOGIN_PARAM->shortValue())->defaultValue('email')->end()
                ->scalarNode(ConfigurationParam::PASSWORD_PARAM->shortValue())->defaultValue('password')->end()
                ->scalarNode(ConfigurationParam::SECRET_KEY->shortValue())->defaultValue('%env(RKWADRIGA_JWT_SECRET)%')->end()
                ->scalarNode(ConfigurationParam::ENCODING_ALGORITHM->shortValue())->defaultValue(Algorithm::SHA256->value)->end()
                ->scalarNode(ConfigurationParam::ENCODING_HASHING_COUNT->shortValue())->defaultValue(3)->end()
                ->integerNode(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME->shortValue())->defaultValue(3600)->end()
                ->integerNode(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME->shortValue())->defaultValue(15552000)->end()
                ->scalarNode(ConfigurationParam::ACCESS_TOKEN_LOCATION->shortValue())->defaultValue(TokenParamLocation::HEADER->value)->end()
                ->scalarNode(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME->shortValue())->defaultValue('Authorization')->end()
                ->scalarNode(ConfigurationParam::REFRESH_TOKEN_LOCATION->shortValue())->defaultValue(TokenParamLocation::BODY->value)->end()
                ->scalarNode(ConfigurationParam::REFRESH_TOKEN_PARAM_NAME->shortValue())->defaultValue('refresh_token')->end()
                ->scalarNode(ConfigurationParam::TOKEN_TYPE->shortValue())->defaultValue(TokenParamType::BEARER->value)->end()
                ->booleanNode(ConfigurationParam::REFRESH_TOKEN_IN_DB->shortValue())->defaultValue(true)->end()
                ->scalarNode(ConfigurationParam::REFRESH_TOKEN_TABLE->shortValue())->defaultValue('refresh_token')->end()
                ->integerNode(ConfigurationParam::REFRESH_TOKENS_LIMIT->shortValue())->defaultValue(3)->end()
                ->booleanNode(ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED->shortValue())->defaultValue(true)->end()
            ->end()
        ;

        return $threeBuilder;
    }
}