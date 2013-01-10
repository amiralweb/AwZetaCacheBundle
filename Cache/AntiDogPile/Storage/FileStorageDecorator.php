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

class FileStorageDecorator extends AntiDogPileStorageDecorator
{

    private $lockItemResources = array();

    /**
     * Acquire a lock on the storage.
     *
     * This method acquires a lock on the storage. If locked, the storage must
     * block all other method calls until the lock is freed again using {@link
     * ezcCacheStackMetaDataStorage::unlock()}. Methods that are called within
     * the request that successfully acquired the lock must succeed as usual.
     *
     * @return void
     */
    public function lockItem($id, $attributes = null)
    {

        $lockItemFilePath = $this->getLockItemFilePath($id, $attributes);

        $this->lockItemResources[$lockItemFilePath] = @fopen($lockItemFilePath, 'x');

        while ($this->lockItemResources[$lockItemFilePath] === false) {

            clearstatcache();

            $this->lockItemResources[$lockItemFilePath] = @fopen($lockFile, 'x');

            // Wait for lock to get freed
            if ($this->lockItemResources[$lockItemFilePath] === false) {
                usleep($this->getOptions()->lockWaitTime);
            }

            // Check if lock is to be considered dead. Might result in a
            // nonrelevant race condition if the lock file disappears between
            // fs calls. To avoid warnings in this case, the calls are
            // silenced.
            if (file_exists($lockItemFilePath) && (time() - @filemtime($lockItemFilePath)) > $this->getOptions()->maxLockTime) {
                @unlink($lockItemFilePath);
            }
        }
    }

    /**
     * Release a lock on the storage.
     *
     * This method releases the lock of the storage, that has been acquired via
     * {@link ezcCacheStackMetaDataStorage::lock()}. After this method has been
     * called, blocked method calls (including calls to lock()) can suceed
     * again.
     *
     * @return void
     */
    public function unlockItem($id, $attributes = null)
    {
        $lockItemFilePath = $this->getLockItemFilePath($id, $attributes);

        // If the resource is already removed, nothing to do
        if (!empty($this->lockItemResources[$lockItemFilePath])) {
            fclose($this->lockItemResources[$lockItemFilePath]);
            @unlink($lockItemFilePath);
            unset($this->lockItemResources[$lockItemFilePath]);
        }
    }

    public function getLockItemFilePath($id, $attributes = null)
    {

        return $lockFile = $this->getLocation() . $this->generateIdentifier($id, $attributes) . $this->getOptions()->lockFile;
    }

}
