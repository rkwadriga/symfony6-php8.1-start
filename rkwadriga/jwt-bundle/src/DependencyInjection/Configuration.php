<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $threeBuilder = new TreeBuilder('rkwadriga_jwt');
        $threeBuilder->getRootNode()
            ->children()
                ->scalarNode('login_url')->defaultValue('rkwadriga_jwt_auth_login')->end()
                ->scalarNode('login_pram')->defaultValue('email')->end()
                ->scalarNode('password_param')->defaultValue('password')->end()
                ->scalarNode('secret_key')->defaultValue('%env(SECRET_KEY)%')->end()
                ->scalarNode('encoding_algorithm')->defaultValue('SHA256')->end()
                ->integerNode('access_token_life_time')->defaultValue(3600)->end()
                ->integerNode('refresh_token_life_time')->defaultValue(15552000)->end()
            ->end()
        ;

        return $threeBuilder;
    }
}