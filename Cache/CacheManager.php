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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheManager extends \ezcCacheManager
{

    /**
     * Wrapper method to Fit with Symfony DIC as i found no way to define a service with
     * pre-cofiguration callback. It's here just to handels automatic creation of cache locations as it's not
     * provided by default in ezCacheManager
     *
     * Returns the ezcCacheStorage object with the given ID.
     * If the cache Id is not allready defined with {@link ezcCacheManager::createCache()} method
     * it will be defined using the other parameters. If no instance of this
     * cache does exist yet, it's created on the fly. If one exists, it will
     * be reused.
     *
     * @param string $id                   The ID of the cache to return.
     * @param string $location             Location to create the cache in. Null for
     *                                       memory-based storage and an existing
     *                                       writeable path for file or memory/file
     *                                       storage.
     * @param string $storageClass           Subclass of {@link ezcCacheStorage}.
     * @param array(string=>string) $options Options for the cache.
     * @return ezcCacheStorage The cache with the given ID.
     *
     * @throws ezcCacheInvalidIdException
     *         If the ID of a cache you try to access does not exist. To access
     *         a cache using this method, it first hast to be created using
     *         {@link ezcCacheManager::createCache()}.
     * @throws ezcBaseFileNotFoundException
     *         If the storage location does not exist. This should usually not
     *         happen, since {@link ezcCacheManager::createCache()} already
     *         performs sanity checks for the cache location. In case this
     *         exception is thrown, your cache location has been corrupted
     *         after the cache was configured.
     * @throws ezcBaseFileNotFoundException
     *         If the storage location is not a directory. This should usually
     *         not happen, since {@link ezcCacheManager::createCache()} already
     *         performs sanity checks for the cache location. In case this
     *         exception is thrown, your cache location has been corrupted
     *         after the cache was configured.
     * @throws ezcBaseFilePermissionException
     *         If the storage location is not writeable. This should usually not
     *         happen, since {@link ezcCacheManager::createCache()} already
     *         performs sanity checks for the cache location. In case this
     *         exception is thrown, your cache location has been corrupted
     *         after the cache was configured.
     * @throws ezcBasePropertyNotFoundException
     *         If you tried to set a non-existent option value. The accepted
     *         options depend on the ezcCacheStorage implementation and may
     *         vary.
     * @throws ezcBaseFilePermissionException
     *         If the given location is not read/writeable (thrown by sanity
     *         checks performed when storing the configuration of a cache to
     *         ensure the latter calls to {@link ezcCacheManager::getCache()}
     *         do not fail).
     * @throws ezcCacheUsedLocationException
     *         If the given location is already in use by another cache.
     * @throws ezcCacheInvalidStorageClassException
     *         If the given storage class does not exist or is no subclass of
     *         ezcCacheStorage.
     */

    public static function acquireCache($id, $location = null, $storageClass, $options = array())
    {

        try {

            $cache = static::getCache($id);

        } catch (\ezcCacheInvalidIdException $e) {
            try {

                static::createCache($id, $location, $storageClass, $options);

            } catch (\ezcBaseFileNotFoundException $e) {

                $fs = new Filesystem();

                if ($fs->isAbsolutePath($location)) {
                    try {

                        $fs->mkdir($location, 0777);

                    } catch (IOException $e) {

                        throw new \ezcBaseFileNotFoundException($location, 'cache location', 'Does not exist and is not writable. Details : '
                                . $e->getMessage());
                    }

                    static::createCache($id, $location, $storageClass, $options);
                }
            }
        }

        $cache = static::getCache($id);

        return $cache;

    }

}
