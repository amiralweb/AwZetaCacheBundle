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

namespace Aw\ZetaCacheBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CacheDeleteCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('aw:zeta-cache:delete')->setDescription('Deletes specific cache items from specific storages')
                ->addArgument('storage-id', InputArgument::REQUIRED, 'The storage id')
                ->addArgument('cache-id', InputArgument::OPTIONAL, 'The cache item id', null)
                ->addOption('attribute', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                        'The cache item attribute identifier, in this format [attribute_name:value] value can be empty if you want to match any value',
                        array())->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, will delete items either if they have any attributes');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storageId = $input->getArgument('storage-id');
        $cacheId = $input->getArgument('cache-id');
        $inAttributes = $input->getOption('attribute');
        $isSearch = (boolean) $input->getOption('force');

        $attributes = $this->convertAttributes($inAttributes);

        $deleted = $this->getContainer()->get('aw_zeta_cache.cache_clearer')->delete($storageId, $cacheId, $attributes, $isSearch);

        $output->writeLn(sprintf('<info>Total deleted items : <fg=red>%s</fg=red></info>', count($deleted)));

        if (empty($deleted)) {
            if (!$isSearch) {
                $output->writeLn(sprintf('<comment>Consider using -f to force deletition of items flaged with attributes</comment>'));
            }

        } elseif ($input->getOption('verbose')) {

            $deleted = '<fr=red>' . implode("</fg=red> - <fg=red> ", $deleted) . '</fr=red>';
            $output->writeLn('<info>Deleted items :</info>');
            $output->writeLn($deleted);

        }

    }

    protected function convertAttributes($inAttributes)
    {
        $attributes = array();
        if (!empty($inAttributes)) {
            foreach ($inAttributes as $attribute) {

                if (!strpos($attribute, ':')) {
                    throw new \RuntimeException(sprintf('Cache attribute "%s" should be in this format [attribut_name:vaule]', $attribute));
                }
                list($name, $value) = explode(':', $attribute, 2);
                $attributes[$name] = $value;
            }
        }

        return $attributes;

    }
}
