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

namespace Aw\ZetaCacheBundle\Cache\AntiDogPile\Storage;
use Aw\ZetaCacheBundle\Cache\AntiDogPile\AntiDogPileStorageDecorator;

class MemoryStorageDecorator extends AntiDogPileStorageDecorator
{

    private $lockItemKey = array();

    /**
     * Acquire a lock for the item on the storage.
     *
     * This method acquires a lock on the storage for the item. If locked, the storage must
     * block all other method calls until the lock is freed again using {@link
     * ezcCacheStackMetaDataStorage::unlock()}. Methods that are called within
     * the request that successfully acquired the lock must succeed as usual.
     *
     * @return void
     */
    public function lockItem($id, $attributes = null)
    {
        $lockKey = $this->getLockItemKey($id, $attributes);
        $this->getProtedtedPropertyValue('backend')->acquireLock($lockKey, $this->getOptions()->lockWaitTime, $this->getOptions()->maxLockTime);
        $this->lockItemKey[$lockKey] = true;
    }

    /**
     * Release a lock for the item on the storage.
     *
     * This method releases the lock of the storage, that has been acquired via
     * lockItem. After this method has been called,
     *  blocked method calls (including calls to lockItem()) can suceed
     * again.
     *
     * @return void
     */
    public function unlockItem($id, $attributes = null)
    {
        $lockKey = $this->getLockItemKey($id, $attributes);

        if (isset($this->lockItemKey[$lockKey])) {

            $this->getProtedtedPropertyValue('backend')->releaseLock($lockKey);
            unset($this->lockItemKey[$lockKey]);
        }
    }

    public function getLockItemKey($id, $attributes = null)
    {
        $key = urldecode($this->generateIdentifier($id, $attributes));

        return urlencode($key . '_' . $this->getOptions()->lockKey);

    }

}
