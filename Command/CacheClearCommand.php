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
use Aw\ZetaCacheBundle\Cache\Exception\MetaStorageException;

class CacheClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('aw:zeta-cache:clear')->setDescription('Reset the given storage')
                ->addArgument('storage-id', InputArgument::OPTIONAL, 'The storage id (required)', false)
                ->addOption('all', 'a', InputOption::VALUE_NONE, 'Set it to clear all storages, in this case [storage-id] argument is not required')
                ->addOption('force', 'f', InputOption::VALUE_NONE, 'Set it to bypass confirmation message');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storageId = $input->getArgument('storage-id');
        $isForce = (boolean) $input->getOption('force');
        $clearAll = (boolean) $input->getOption('all');

        if (!$clearAll && $storageId === false) {

            throw new \RuntimeException(sprintf('Provide [storage-id] or use -a to clear all storages'));
        }

        $dialog = $this->getHelperSet()->get('dialog');
        $message = $clearAll ? '<question>Are you sure you want to reset all sorages ? (y to confirm or any key to cancel) </question>'
                : '<question>Are you sure you want to reset the storage <bg=red>%s</bg=red> ? (y to confirm or any key to cancel) </question>';

        if (!$isForce) {
            if (!$dialog->askConfirmation($output, sprintf($message, $storageId), false)) {
                $output->writeLn('<info>Canceled</info>');

                return;
            }
        }

        if ($clearAll) {
            $clearedList = $this->getContainer()->get('aw_zeta_cache.cache_clearer')->clearAll();

            if (!empty($clearedList)) {

                $deleted = '<fg=magenta>' . implode("</fg=magenta> - <fg=magenta> ", $clearedList) . '</fg=magenta>';
                $output->writeLn(sprintf('<info>Deleted Storages :<fg=magenta>%s</fg=magenta></info>', count($clearedList)));
                $output->writeLn($deleted);
            }
        } else {
            try {
                $this->getContainer()->get('aw_zeta_cache.cache_clearer')->reset($storageId, $isForce);
            } catch (MetaStorageException $e) {
                $output->writeLn(sprintf('<error>%s</error>', $e->getMessage()));

                if (!$dialog->askConfirmation($output, 'Do you steel want to clear this storage ? (y to confirm or any key to cancel)', false)) {
                    $output->writeLn('Canceled');

                    return;
                }

                $this->getContainer()->get('aw_zeta_cache.cache_clearer')->reset($storageId, true);

            }
        }

        $output->writeLn('<info>Done</info>');

    }

}
