<?php

 /**
 * This file is part of AwZetaCacheBundle
 *
 * @author    Mohamed Karnichi <mka@amiralweb.com>
 * @copyright 2013 Amiral Web
 * @link      http://www.amiralweb.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aw\ZetaCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('aw_zeta_cache');

        $rootNode
            ->children()
                ->booleanNode('dev_mode')->defaultFalse()->treatNullLike(true)->end()
                ->booleanNode('dog_pile_protection')->defaultFalse()->treatNullLike(true)->end()
                ->booleanNode('app_clear')->defaultFalse()->treatNullLike(true)->end()
                ->arrayNode('common_storage_options')->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('common_stack_options')
                    ->children()
                        ->scalarNode('replacementStrategy')->cannotBeEmpty()->end()
                        ->booleanNode('bubbleUpOnRestore')->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('storages')->requiresAtLeastOneElement()->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                             ->booleanNode('dog_pile_protection')->defaultFalse()->treatNullLike(true)->end()
                             ->booleanNode('app_clear')->treatNullLike(true)->end()
                             ->scalarNode('storage_class')->isRequired()->cannotBeEmpty()->end()
                             ->scalarNode('location')->isRequired()->cannotBeEmpty()->end()
                             ->arrayNode('options')
                                ->prototype('variable')->end()
                             ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('stacks')->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                             ->booleanNode('app_clear')->treatNullLike(true)->end()
                             ->arrayNode('storages')->requiresAtLeastOneElement()->useAttributeAsKey('id', false)
                                 ->prototype('array')
                                     ->children()
                                         ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                                         ->scalarNode('itemLimit')->isRequired()->cannotBeEmpty()
                                             ->validate()
                                                 ->ifTrue(function($v){return ( !is_int( $v ) || $v < 1 );})
                                                 ->thenInvalid('itemLimit should be an int > 1')
                                             ->end()
                                         ->end()
                                         ->scalarNode('freeRate')->isRequired()->cannotBeEmpty()
                                             ->validate()
                                                 ->ifTrue(function($v){return ( !is_numeric( $v ) ||  $v <= 0 || $v > 1 );})
                                                 ->thenInvalid('freeRate shoud be a float > 0 and <= 1')
                                             ->end()
                                         ->end()
                                     ->end()
                                 ->end()
                             ->end()
                             ->arrayNode('options')
                                ->children()
                                    ->scalarNode('metaStorage')->cannotBeEmpty()->end()
                                    ->scalarNode('replacementStrategy')->cannotBeEmpty()->end()
                                    ->booleanNode('bubbleUpOnRestore')->cannotBeEmpty()->end()
                                 ->end()
                             ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

            return $treeBuilder;
    }
}
