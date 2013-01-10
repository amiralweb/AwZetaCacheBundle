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

namespace Aw\ZetaCacheBundle;
use Aw\ZetaCacheBundle\DependencyInjection\Compiler\AntiDogPileCompilerPass;

use Aw\ZetaCacheBundle\DependencyInjection\Compiler\CacheClearerCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AwZetaCacheBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AntiDogPileCompilerPass());
        $container->addCompilerPass(new CacheClearerCompilerPass());

    }
}
