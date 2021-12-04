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
                ->scalarNode('login_url')->end()
                ->scalarNode('login_pram')->end()
                ->scalarNode('password_param')->end()
            ->end()
        ;

        return $threeBuilder;
    }
}