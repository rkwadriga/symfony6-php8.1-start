<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

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
                ->scalarNode('login_pram')->defaultValue('email')->end()
                ->scalarNode('password_param')->defaultValue('password')->end()
                ->scalarNode('keys_dir')->defaultValue('config/jwt')->end()
                ->scalarNode('private_key_name')->defaultValue('private.pem')->end()
                ->scalarNode('public_key_name')->defaultValue('public.pem')->end()
            ->end()
        ;

        return $threeBuilder;
    }
}