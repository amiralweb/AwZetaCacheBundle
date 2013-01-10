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

namespace Aw\ZetaCacheBundle\Cache\Exception;

/**
 * Thrown when trying to reset a storage used by a stack as meta Storage
 */

class MetaStorageException extends \RuntimeException
{



    public function __construct($storageId, $stackId)
    {
        $this->message = sprintf(
                'The storage "%s" is used as metaSorage for the stack "%s". Resetting it can make the complete stack inconsistent, instead clear the stack "%s"',
                $storageId, $stackId, $stackId);
    }
}
