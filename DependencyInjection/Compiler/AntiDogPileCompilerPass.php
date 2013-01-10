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
use Aw\ZetaCacheBundle\DependencyInjection\DiUtils;

use Aw\ZetaCacheBundle\AwZetaCacheBundle;
use Aw\ZetaCacheBundle\DependencyInjection\AwZetaCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;

class AntiDogPileCompilerPass implements CompilerPassInterface
{

    const DOGPILE_FEARFUL_SERVICE_AFFIX = 'dogpile_fearful.';

    public function process(ContainerBuilder $container)
    {

        $storagesToProtect = $container->findTaggedServiceIds(AwZetaCacheExtension::DOGPILE_PROTECTION_TAG);

        foreach ($storagesToProtect as $storageDefId => $parameters) {

            $storageDef = $container->getDefinition($storageDefId);
            $exposedStorageDefId = self::getExposedStorageDefinitionId($storageDefId);

            // Create new  definition for the original (exposed) storage
            $container->setDefinition($exposedStorageDefId, $storageDef)->isPublic(false);

            // Swap storage definition : Use decorated storage service instead of the original storage
            $container->setDefinition($storageDefId, new Definition('%aw_zeta_cache.anti_dog_pile_decorator_abstract.class%'))
                    ->setFactoryClass('%aw_zeta_cache.anti_dog_pile_decorator_factory.class%')->setFactoryMethod('buildCoveredStorage')
                    ->addArgument(new Reference($exposedStorageDefId));
        }
    }

    public static function getExposedStorageDefinitionId($definitionId)
    {
        return str_replace(AwZetaCacheExtension::CACHE_SERVICE_PREFIX, AwZetaCacheExtension::CACHE_SERVICE_PREFIX . self::DOGPILE_FEARFUL_SERVICE_AFFIX,
                $definitionId);
    }

}
