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

namespace Aw\ZetaCacheBundle\Cache\AntiDogPile;
/**
 *
 * This is what i call an "Insane Decorator" as this is a Decorator that doses not implement decorated element interface,
 * it simulates the behavior change during execution. I wish if there was an interface for ezcCacheStorage instead of the abstract class...
 * A cleaner implementation is possible thanks to Traits, then we'll be able to extend Storage classes directly and inject lock protection logic through Traits
 * without having to duplicate any method implementation...
 * It's not entended to by used as implementation of storage !! It's just the simplest implementation if found to make all zeta components
 * cache storages protected from the cache dog pile effect (excluding stack storage witch already handel this).
 * This "Decorator" ensures that all configurations, interaction are done the right way, as it will forward all calls to
 * the decorated storage.
 *
 */

abstract class AntiDogPileStorageDecorator
{
    protected $coveredStorage;

    public function __construct(\ezcCacheStorage $storage)
    {
        $this->coveredStorage = $storage;
    }

    public function store($id, $data, $attributes = array())
    {
        $this->lockItem($id, $attributes);

        $result = $this->coveredStorage->store($id, $data, $attributes);

        $this->unlockItem($id, $attributes);

        return $result;

    }

    public function restore($id, $attributes = array(), $search = false)
    {

        $this->lockItem($id, $attributes);

        $result = $this->coveredStorage->restore($id, $attributes, $search);

        $this->unlockItem($id, $attributes);

        return $result;

    }

    public function delete($id, $attributes = array(), $search = false)
    {

        $this->lockItem($id, $attributes);

        $result = $this->coveredStorage->delete($id, $attributes, $search);

        $this->unlockItem($id, $attributes);

        return $result;

    }

    // forward all to decorated storage

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->coveredStorage, $method), $args);
    }

    public function __get($property)
    {
        return call_user_func(array($this->coveredStorage, '__get'), $property);
    }

    public function __set($property, $value)
    {
        return call_user_func(array($this->coveredStorage, '__set'), $property, $value);
    }

    public function __isset($propertyName)
    {
        return call_user_func(array($this->coveredStorage, '__isset'), $propertyName);
    }

    /**
     * Handy way to get the value of protected properties that dont have any accessor method (ex $backend in memoryStorage)
     *
     * @param string $propertyName
     * @return mixed The value of the protected property.
     */
    protected function getProtedtedPropertyValue($propertyName)
    {
        $reflection = new \ReflectionClass($this->coveredStorage);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($this->coveredStorage);
    }

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
    abstract public function lockItem($id, $attributes = null);

    /**
     * Release the lock for the item on the storage.
     *
     * This method releases the lock of the storage, that has been acquired via
     * lockItem. After this method has been called,
     * blocked method calls (including calls to lockItem()) can suceed
     * again.
     *
     * @return void
     */

    abstract public function unlockItem($id, $attributes = null);
}

