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

namespace Aw\ZetaCacheBundle\DependencyInjection\Compiler;
use Aw\ZetaCacheBundle\DependencyInjection\AwZetaCacheExtension;
use Aw\ZetaCacheBundle\DependencyInjection\DiUtils;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class CacheClearerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $cacheClearerDef = $container->getDefinition('aw_zeta_cache.cache_clearer');
        $storageParameters = $container->findTaggedServiceIds(AwZetaCacheExtension::STORAGE_TAG);

        foreach ($storageParameters as $parameters) {
            $cacheClearerDef->addMethodCall('addStorage', array($parameters[0]['id']));
        }

        $stackParameters = $container->findTaggedServiceIds(AwZetaCacheExtension::STACK_TAG);
        foreach ($stackParameters as $parameters) {
            // Tagged only once
            $parameters = $parameters[0];
            $storageIdList = explode('#!', $parameters['storage_id_list']);
            $stackId = $parameters['id'];
            $cacheClearerDef->addMethodCall('addStack', array($stackId, $storageIdList));

        }

        $appClearableParameters = $container->findTaggedServiceIds(AwZetaCacheExtension::APP_CLEAR_TAG);

        foreach ($appClearableParameters as $parameters) {
            $cacheClearerDef->addMethodCall('flagAsAppClearable', array($parameters[0]['id']));
        }

        $metaStorageParameters = $container->findTaggedServiceIds(AwZetaCacheExtension::META_STORAGE_TAG);
        foreach ($metaStorageParameters as $parameters) {
            $cacheClearerDef->addMethodCall('flagAsMetaStorage', array($parameters[0]['id'], $parameters[0]['stack_id']));
        }

    }
}
