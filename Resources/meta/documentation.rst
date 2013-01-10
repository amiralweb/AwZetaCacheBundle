Installation
============

1. With Composer add the following to your `composer.json` file, then run composer `update` command

.. code-block:: json

    // composer.json
    {
        // ...
        require: {
            // ...
            "aw/zeta-cache-bundle": "dev-master"
        }
    }
..



2. Register AwZetaCacheBundle by adding following to `AppKernel.php` file:

.. code-block:: php

    <?php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Aw\ZetaCacheBundle\AwZetaCacheBundle,
        // ...
    );


Usage
=====

Please before continuing juste take time to read `Zeta Cache Component tutorial <http://zetacomponents.org/documentation/trunk/Cache/tutorial.html>`_
you will be able to maximize the use of AwZetaCacheBundle


Stand Alone storage
-------------------

Configuration
~~~~~~~~~~~~~

Add this to your config.yml file

.. code-block:: yaml

    # config.yml
    aw_zeta_cache:
        dev_mode: %kernel.debug%                                     # Optional, if set all cached data will be flaged as outdated (invalid), 
                                                                     # so you dont have to clear the cache while you are in dev mode :)
        dog_pile_protection: true                                    # Optional, if set, stand alone storages (that are not used in stack)
                                                                     # will be protected against Dog Pile effect
        storages:
          my_cache_id:
             app_clear: false                                        # Optional to flush the cache whe using command line :  php app/console cache:clear
             storage_class: ezcCacheStorageFileObject                # Required Storage class name : could be any classStorage name see 
                                                                     # http://zetacomponents.org/documentation/trunk/Cache/tutorial.html
                                                                     # Available Classes are listed in [this file](../config/parameters.yml)





             location: %kernel.cache_dir%/my_cache                  # Required: where cache will be stored
             options:                                                # Under options you can declare any Storage options parameter 'case sensitive'
                                                                     # for example for ezcCacheStorageFileObject, all supported parameters are 
                                                                     # documented in coresponding Options Class ezcCacheStorageFileOptions
                                                                     # see http://zetacomponents.org/documentation/trunk/Cache/phpdoc/ezcCacheStorageFileOptions.html
                                                                     # for example : extension,  lockFile, lockWaitTime, maxLockTime, permissions, ttl
                                                                     # here we will define the ttl parameter

                    ttl: 3600 #(default 1 day if not configured)     # The cache time to life


Service usage
~~~~~~~~~~~~~

.. code-block :: php
 
    // The cache service name is concatenation of aw_zeta_cache.cache. and configured storage id

    $cache = $container->get('aw_zeta_cache.cache.my_cache_id');    

    // Check if $data is in cache and is fresh
    if (($data = $cache->restore('list') === false) {
            // the data is missing or is outdated
            // so make it and store it in cache
            $data = 'data that consumes a lot of resources';
            $cacheStack->store('list',$data);

     }


     // use $data


Another example to set cache attribute :

.. code-block :: php

        $cache = $container->get('aw_zeta_cache.cache.my_cache_id');

         if (($data = $cache->restore('list', array('location'=>25))) === false) {
                // the data is missing or is outdated
                // so make it and store it in cache
                // The attribute/value extends the key of the cache
                // for example it could be a parameter to your controller
                $data = 'data is related to my application parameter location value 25'
                $cacheStack->store('list',$data, array('location'=>25));

         }

         // you can even store cache with the same key, and the same attribute key but with different value

         if (($data = $cache->restore('list', array('location'=> 125))) === false) {
                // the data is missing or is outdated
                // so make it and store it in cache
                $data = 'data is related to my application parameter location value 125'
                $cacheStack->store('list',$data, array('location'=>125));

         }

         // you can retrive cached resources by parameter then add third parameter true 
         //to enable lookup ignoring attribute value

         if (($data = $cache->restore('list', array('location')), true) !== false) {
               // use $data

         }


        // you can combine multiple parameters ( this example is from 
        // `Zeta Cache Component docs <http://zetacomponents.org/documentation/trunk/Cache/tutorial.html>`_

        $exampleData = array(
                                'unique_id_3_a' => array( 'language' => 'en', 'section' => 'articles' ),
                                'unique_id_3_b' => array( 'language' => 'de', 'section' => 'articles' ),
                                'unique_id_3_c' => array( 'language' => 'no', 'section' => 'articles' ),
                                'unique_id_3_d' => array( 'language' => 'de', 'section' => 'tutorials' ),
                                );

        $cache = ezcCacheManager::getCache( 'array' );

        foreach ( $exampleData as $myId => $exampleDataArr )
        {
            if ( ( $data = $cache->restore( $myId, $exampleDataArr ) ) === false )
            {
                     $cache->store( $myId, $exampleDataArr, $exampleDataArr );
            }
        }

        echo "Data items with attribute <section> set to <articles>: " .
        $cache->countDataItems( null, array( 'section' => 'articles' ) ) .
        "\n";
        echo "Data items with attribute <language> set to <de>: " .
        $cache->countDataItems( null, array( 'language' => 'de' ) ) .
        "\n\n";

        // delete all items having attribute 'language' set to 'de'
        $cache->delete( null, array( 'language' => 'de' ) );
        echo "Data items with attribute <section> set to <articles>: " .
        $cache->countDataItems( null, array( 'section' => 'articles' ) ) .
        "\n";
        echo "Data items with attribute <language> set to <de>: " .
        $cache->countDataItems( null, array( 'language' => 'de' ) ) .
        "\n\n";


Stacked Storages
----------------

Configuration
~~~~~~~~~~~~~
Just define multiple storage, just like if the were used stand alone
Then use them in stack storage

Add this to your config.yml file

.. code

    // config.yml
    aw_zeta_cache:
        dev_mode: %kernel.debug%
        dog_pile_protection: true
        storages:
              my_file_cache:
                 storage_class: ezcCacheStorageFileObject
                 location: "%kernel.cache_dir%/my_cache                  # Required: where cache will be stored
                 options:
                    ttl: 3600 #(default 1 day if not configured)         # The cache time to life

             my_memcache_cache:
                storage_class: ezcCacheStorageMemcachePlain
                location: 'memcache'                                    # the location is optionnal for in memory storages
                options:
                    host: localhost
                    ttl: 3600

       stacks:
            my_stack:
                storages:
                    - { id: file_cache, itemLimit: 10000, freeRate: 0.5 }
                    - { id: my_memcache_cache, itemLimit: 1000, freeRate: 0.3 }
                options:
                    replacementStrategy: ezcCacheStackLfuReplacementStrategy      # optional: Replacement strategy default  
                                                                                  # is ezcCacheStackLruReplacementStrategy (LRU)
                                                                                  # for LFU then use ezcCacheStackLfuReplacementStrategy

From here you can use the defined stack just like a simple storage

.. code-block :: php

        <?php

        $cache = $container->get('aw_zeta_cache.cache.my_stack');

        // Check if $data is in cache and is fresh
        if (($data = $cache->restore('list') === false) {
                // the data is missing or is outdated
                // so make it and store it in cache
                $data = 'data that consumes a lot of resources';
                $cacheStack->store('list',$data);

         }

Full configuration
==================
Doc To be completed

Command line
============

Doc To be completed

Cache Clearer Service
==================

Doc To be completed







