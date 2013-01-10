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
use Aw\ZetaCacheBundle\Cache\AntiDogPile\Storage\MemoryStorageDecorator;
use Aw\ZetaCacheBundle\Cache\AntiDogPile\Storage\FileStorageDecorator;

class AntiDogPileStorageFactory
{

    public static function buildCoveredStorage(\ezcCacheStorage $storage)
    {

        $reflection = new \ReflectionClass($storage);

        if ($reflection->isSubclassOf('ezcCacheStorageFile')) {

            return new FileStorageDecorator($storage);

        } elseif ($reflection->isSubclassOf('ezcCacheStorageMemory')) {

            return new MemoryStorageDecorator($storage);

        }

        throw new \RuntimeException(sprintf('The cache storage using location "%s" does not support dog pile protection', $storage->getLocation()));

    }

}

