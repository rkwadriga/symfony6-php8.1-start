<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenIdentifier;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $threeBuilder = new TreeBuilder('rkwadriga_jwt');
        $threeBuilder->getRootNode()
            ->children()
                ->scalarNode('login_url')->defaultValue('rkwadriga_jwt_auth_login')->end()
                ->scalarNode('refresh_url')->defaultValue('rkwadriga_jwt_refresh_token')->end()
                ->scalarNode('login_pram')->defaultValue('email')->end()
                ->scalarNode('password_param')->defaultValue('password')->end()
                ->scalarNode('secret_key')->defaultValue('%env(SECRET_KEY)%')->end()
                ->scalarNode('encoding_algorithm')->defaultValue('SHA256')->end()
                ->integerNode('access_token_life_time')->defaultValue(3600)->end()
                ->integerNode('refresh_token_life_time')->defaultValue(15552000)->end()
                ->scalarNode('access_token_location')->defaultValue(TokenIdentifier::LOCATION_HEADER)->end()
                ->scalarNode('access_token_param_name')->defaultValue('Authorization')->end()
                ->scalarNode('refresh_token_location')->defaultValue(TokenIdentifier::LOCATION_BODY)->end()
                ->scalarNode('refresh_token_param_name')->defaultValue('refresh_token')->end()
                ->scalarNode('token_type')->defaultValue(TokenIdentifier::TYPE_BEARER)->end()
            ->end()
        ;

        return $threeBuilder;
    }
}