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

namespace Aw\ZetaCacheBundle\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Aw\ZetaCacheBundle\Cache\Exception\MetaStorageException;

class CacheClearer implements CacheClearerInterface, ContainerAwareInterface
{

    protected $storages;
    protected $stacks;
    protected $all;
    protected $appClearables;
    protected $stackedStorages;
    protected $metaStorages;
    protected $container;
    protected $cacheServicePrefix;

    // clear = reset used for storages, stacks
    // delete used for cache items

    public function __construct($cacheServicePrefix)
    {
        $this->cacheServicePrefix = $cacheServicePrefix;
        $this->storages = array();
        $this->stacks = array();
        $this->all = array();
        $this->appClearables = array();
        $this->stackedStorages = array();
        $this->metaStorages = array();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @throws \ezcBaseFilePermissionException
     */
    public function clear($cacheDir)
    {
        foreach ($this->appClearables as $id) {
            $this->reset($id);
        }

    }

    public function setCacheServicePrefix($cacheServicePrefix)
    {
        $this->cacheServicePrefix = $cacheServicePrefix;
    }

    public function addStorage($id)
    {

        $this->storages[] = $id;
        $this->all[] = $id;
    }

    public function addStack($id, $stackStorageIdList)
    {
        $this->stacks[] = $id;
        $this->all[] = $id;
        $this->stackedStorages = array_unique(array_merge($this->stackedStorages, $stackStorageIdList));
    }

    public function flagAsAppClearable($id)
    {
        $this->appClearables[] = $id;
    }

    public function flagAsMetaStorage($storageId, $stackId)
    {
        $this->metaStorages[$storageId] = $stackId;
    }

    public function delete($storageId, $cacheId = null, $attributes = array(), $search = false)
    {

        if (!in_array($storageId, $this->all)) {
            throw new \InvalidArgumentException('Unknown cache storage id ' . $storageId);
        }

        $cacheService = $this->getCacheService($storageId);
        return $cacheService->delete($cacheId, $attributes, $search);
    }

    /**
     * You should never reset a storage used as metaStorage as this can make the complete stack inconsistent
     *
     * @param string     $id
     * @throws \ezcCacheInvalidIdException
     * @throws \ezcBaseFilePermissionException
     * @throws Aw\ZetaCacheBundle\Cache\MetaStorageException
     */
    public function reset($id, $force = false)
    {

        if (!in_array($id, $this->all)) {
            throw new \InvalidArgumentException('Unknown cache storage id ' . $id);
        }

        if (array_key_exists($id, $this->metaStorages) && !$force) {
            $stackId = $this->metaStorages[$id];
            throw new MetaStorageException($id, $stackId);
        }

        $storage = $this->getCacheService($id);

        $result = $storage->reset();

        return $result;

    }

    /**
     * @throws \ezcBaseFilePermissionException
     */
    public function clearAll()
    {
        //prevents double reset on storages used in stacks
        $storages = array_diff($this->all, $this->stackedStorages);

        foreach ($storages as $id) {
            $this->reset($id);
        }

        return $storages;

    }

    protected function getCacheService($id)
    {
        return $this->container->get($this->cacheServicePrefix . $id);
    }

}
