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
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AwZetaCacheExtension extends Extension
{

    // I use those constant here as all next compiler passes are dependent by nature of the Extension

    const CACHE_SERVICE_PREFIX = 'aw_zeta_cache.cache.';
    const STORAGE_TAG = 'aw_zeta_cache.tag.storage';
    const STACK_TAG = 'aw_zeta_cache.tag.stack';
    const DOGPILE_PROTECTION_TAG = 'aw_zeta_cache.tag.dog_pile_protection';
    const APP_CLEAR_TAG = 'aw_zeta_cache.tag.app_clearable';
    const META_STORAGE_TAG = 'aw_zeta_cache.tag.meta_storage';

    public function load(array $configs, ContainerBuilder $container)
    {

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        if (empty($config['storages'])) {
            return;
        }

        $globalStorageOptions = isset($config['common_storage_options']) ? $config['common_storage_options'] : array();
        $globalAppClear = $config['app_clear'];
        $globalDogPileProtection = $config['dog_pile_protection'];
        $isDevMode = isset($config['dev_mode']) ? $config['dev_mode'] : false;

        // Caches definitions

        foreach ($config['storages'] as $storageId => $storageParameters) {

            // Create cache  definition
            $cacheDef = new Definition('%aw_zeta_cache.storage.abstract.class%');
            $cacheDef->setFactoryClass('%aw_zeta_cache.cache_manager.class%')->setFactoryMethod('acquireCache')
                    ->setArguments(array($storageId, $storageParameters['location'], $storageParameters['storage_class']));

            // Set storage Options
            $storageOptions = array();

            if (!empty($storageParameters['options'])) {
                $storageOptions = array_merge($globalStorageOptions, $storageParameters['options']);
            }

            if ($isDevMode) {
                // 2 instead of 0 to let a chance to memory storage connection
                // a PR will be sent to zetaComponents to differenciat cache ttl and storage backend connection timeout
                $storageOptions['ttl'] = 2;
            }

            $cacheDef->addArgument($storageOptions);

            // Flag for dog pile compiler pass
            $dogPileProtection = isset($storageParameters['dog_pile_protection']) ? $config['dog_pile_protection'] : $globalDogPileProtection;
            if ($dogPileProtection) {
                $cacheDef->addTag(AwZetaCacheExtension::DOGPILE_PROTECTION_TAG);
            }

            // Flag Storage for next compiler pass
            $isAppClear = isset($storageParameters['app_clear']) ? $storageParameters['app_clear'] : $globalAppClear;
            if ($isAppClear) {
                $cacheDef->addTag(AwZetaCacheExtension::APP_CLEAR_TAG, array('id' => $storageId));
            }

            // Define cache service
            $cacheDefId = self::getCacheDefinitionIdentifier($storageId);
            $container->setDefinition($cacheDefId, $cacheDef)->addTag(AwZetaCacheExtension::STORAGE_TAG, array('id' => $storageId));

        }

        //Stacks definitions
        if (!empty($config['stacks'])) {

            $this->loadStack($config, $container);
        }

    }

    public function loadStack(array $config, ContainerBuilder $container)
    {

        $commonStackOptions = isset($config['common_stack_options']) ? $config['common_stack_options'] : array();
        $globalAppClear = $config['app_clear'];

        foreach ($config['stacks'] as $stackId => $stack) {

            $stackDefId = self::getCacheDefinitionIdentifier($stackId);

            //to check unicity between stack ids and storages ids

            if ($container->has($stackDefId)) {
                throw new InvalidConfigurationException(
                        sprintf(
                                "aw_zeta_cache.stacks.%s  id is already defined in aw_zeta_caches.caches.%s : all cache ids and stacks ids should be different",
                                $stackId));
            }

            $storages = $stack['storages'];
            $storagesIdList = array();
            $stackDef = new Definition('%aw_zeta_cache.stack.class%', array($stackId));

            foreach ($storages as $storageId => $storageParameters) {

                // List of storage stack elements ids
                $storagesIdList[] = $storageId;
                $cacheDefId = self::getCacheDefinitionIdentifier($storageId);
                $itemLimit = $storageParameters['itemLimit'];
                $freeRate = $storageParameters['freeRate'];

                // Check validity of storage id
                // A storage stack items should be already declared

                if (!$container->has($cacheDefId)) {
                    throw new InvalidConfigurationException(
                            sprintf("aw_zeta_cache.stacks.%s.%s Must be a valid declared cache id in aw_zeta_caches.caches", $stackId, $storageId));
                }

                // Remove dog_pile_protection and app clear tags as this features are handeled at stack level
                $container->getDefinition($cacheDefId)->clearTag(AwZetaCacheExtension::DOGPILE_PROTECTION_TAG)->clearTag(AwZetaCacheExtension::APP_CLEAR_TAG);

                // Create ezcCacheStackStorageConfiguration for this storage
                $stackStorageConfDefId = self::getStackStorageConfDefinitionIdentifier($stackId, $storageId);
                $container->setDefinition($stackStorageConfDefId, new Definition('%aw_zeta_cache.stack.configuration.class%'))
                        ->setArguments(array($storageId, new Reference($cacheDefId), $itemLimit, $freeRate))->setPublic(false);

                // push the storage configuration in stack
                $stackDef->addMethodCall('pushStorage', array(new Reference($stackStorageConfDefId)));
            }

            // MetaStorageId is the last declared storage id
            // It will be used as main storage for the stack meta data
            $metaStorageId = end($storagesIdList);
            $metaStorageDefId = self::getCacheDefinitionIdentifier($metaStorageId);

            if (!empty($stack['options'])) {

                $options = array_merge($commonStackOptions, $stack['options']);

                if (!empty($options['metaStorage'])) {

                    // Override the default metaStorageId
                    $metaStorageId = $options['metaStorage'];
                    $metaStorageDefId = self::getCacheDefinitionIdentifier($metaStorageId);

                    // Storage sanity check
                    if (!$container->hasDefinition($metaStorageDefId)) {

                        throw new InvalidConfigurationException(
                                sprintf('aw_zeta_cache.stacks.%s.options.metaStorage: "%s" Must be the id of one of the defined storages', $stackId, $storageId));
                    }

                    $options['metaStorage'] = new Reference($metaStorageDefId);

                }

                // Create ezcCacheStackOptions for the meta storage
                $stackOptionDefId = self::getStackOptionDefinitionIdentifier($stackId);
                $container->setDefinition($stackOptionDefId, new Definition('%aw_zeta_cache.stack.options.class%', array($options)))->setPublic(false);

                // Push it as last argument for ezcCacheStack constructor
                $stackDef->addArgument(new Reference($stackOptionDefId));
            }

            // Flag meta storage for clearer comiler pass
            $container->getDefinition($metaStorageDefId)->addTag(AwZetaCacheExtension::META_STORAGE_TAG, array('id' => $metaStorageId, 'stack_id' => $stackId));

            // Flag Stag for Cache clear compiler pass
            $isAppClear = isset($stack['app_clear']) ? $stack['app_clear'] : $globalAppClear;
            if ($isAppClear) {
                $stackDef->addTag(AwZetaCacheExtension::APP_CLEAR_TAG, array('id' => $stackId));
            }

            // Create Cache service declaration for the stack
            $container->setDefinition($stackDefId, $stackDef)
                    ->addTag(AwZetaCacheExtension::STACK_TAG,
                            array('id' => $stackId, 'storage_id_list' => implode('#!', $storagesIdList), 'meta_storage_id' => $metaStorageId));
        }

    }

    // Folowing class methods are here to ease extension / customisation for specific usages
    // They can be used from compiler passes

    /**
     * Generates service identifier for the cache
     *
     * @param string $id the storage/stack identifier
     * @return string the cache service identifier
     */
    public static function getCacheDefinitionIdentifier($id)
    {
        return AwZetaCacheExtension::CACHE_SERVICE_PREFIX . $id;
    }

    /**
     * Generates internal (private scope) service id for ezCCacheStackStorageConfiguration
     *
     * @param string $stackId the stack identifier
     * @param string $storageId the storage identifier
     * @return string the service identifier
     */
    public static function getStackStorageConfDefinitionIdentifier($stackId, $storageId)
    {
        return sprintf("aw_zeta_cache_internal.stack_conf.%s_%s", $stackId, $storageId);
    }

    /**
     * Generates internal (private scope) service id for ezCCacheStackStorageConfiguration
     *
     * @param string $stackId the stack identifier
     * @param string $storageId the storage identifier
     * @return string the service identifier
     */
    public static function getStackOptionDefinitionIdentifier($stackId)
    {
        return sprintf("aw_zeta_cache_internal.stack_option.%s", $stackId);
    }

}
