# AwZetaCacheBundle Yet another cache component for Symfony2 SE


## Presentation

AwZetaCacheBundle is a complete integration of the Zeta Cache component in Symfony2 standard edition.

## What are Zeta Components?

> Zeta Components are a high quality, general purpose library of loosly coupled
components for development of applications based on PHP 5.
Zeta Components originally were developed by [eZ Systems (the company behind **eZ Publish Open source Enterprise CMS**)](http://ez.no)
under the name **"eZ Components"** and was
generously sponsored to the Apache Software Foundation for further development.
Since 07/2010 the components are known as "Zeta Components".
Since May 2012 Zeta Components retired from Apache Incubator and is now developed on
[Github](https://github.com/zetacomponents).
**eZ Systems** still builds their [core products](https://github.com/ezsystems) on top of Zeta Components and contributes to the project.

Take a look to the [eZ Publish 5 plateform](https://ez.no/Products/eZ-Publish-5-Platform) it's built on top of Symfony2 full stack framework.


## What is the Zeta Cache Component

> The Cache package provides general purpose caching for all kinds of
imaginable data onto all kinds of imaginable media.

What makes this implementation different than other php cache implementations is:
- Tag valued cache items
- Hierarchical caching (Cache Stacks)
- LFU / LRU Replacement Strategies

Supported repositories:
- FS
- APC
- Memcache

### Complex Caching ( Cache attributes)

> You can flag any cache item with one or more attributes (tags) : (each attribute can have a value).
Attribute s describe a cache item in further detail. You can retrieve cache items by a combination of id (optional) attributes/values (optional but recommended for faster search).


### Hierarchical caching (Cache Stack)

> Allows you to combine very fast caches (like APC and Memcache),
which are mostly small, with slower ones, that are usually quite large.
Similar techniques are used in CPU caches and file system caches.

> Whenever data is stored in a cache stack, it is stored in **all of the stacked storages**.
For each of the storages, a limit is configured that determines how many items may
reside in a cache. If this limit is reached for a storage during the store operation of
a new item, a certain amount of items is removed from that storage to free up space.
The fraction of this limit that is freed when reaching the limit is the "free rate"
of the storage.

> For the freeing of items, the cache stack first purges all outdated items
from the affected storage. If this does not remove the desired number of items, a special
replacement strategy is utilized to free up more items, see below.

### LFU / LRU Replacement Strategy

> The replacement strategy (LFU = Least Frequently Used) will record every access
(store/restore) to a cache item and will purge those first, which have been least frequently accessed.
The default replacement strategy is (LRU = Least Recently Used), which removes such items first,
that have been accessed least recently.

For the full documentation please check [the cache component documentation](http://zetacomponents.org/documentation/trunk/Cache/tutorial.html)
(Some links are broken there, a PR was sent to fix theme. You can still have access to the original documentation
[here](http://ezcomponents.org/docs/tutorials/Cache)  )


## AwZetaCacheBundle : Yet another cache component for Symfony2

This is a **Full integration** of Zeta cache component into Symfony2.

It comes with all cache component features. And some handy capabilities:

- Automatic creation of cache locations:

    As by default for file and memory/file hybrid storage (ezcCacheStorageFileApcArray) the location should be an existing writeable path.

- **Dog Pile effect protection for stand alone storages** :

    You can enable Anti Dog Pile protection for stand alone storages. As the major protection against this effect was only implemented in Stack Storage.
    When enabled, storages that dont belong to any stack will be automatically protected from race conditions in high load environments.

- **Commande line tool** :

    + to delete any cache item by combination of tags/values.
    + to reset any cache backend, or all cache backends.

- **A cache cleaner service** :

    Just like the command line tool, you can use it to clear any storage, or to delete any cache item, from your code.
    you can use it to for example to clear cache items based on events.

- **App level cache cleaning** :

    You can define witch storages backends to clear when the hole Symfony2 application cache is cleared

- **Developer Freindly** DevMode :

    you can configure all you caches and check that all is working fine, you can set the dev_mode in the configuration.
    In this mode all cache items will always be marked as invalid (so you will never have to clear the cache your self).

- And of course for each defined cache storage, a custom service is made, and available on demand.


## Bundle documentation

The bundle documentation is available in [./Resources/meta/documentation.rst](./AwZetaCacheBundle/Resources/meta/documentation.rst).


## License
The code is released under the Apache License, Version 2.0. You can find it here [Resources/meta/LICENCE](./Resources/meta/LICENCE)
