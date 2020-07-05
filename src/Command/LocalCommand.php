<?php

namespace Datashaman\Phial\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LocalCommand extends Command
{
    protected static $defaultName = 'local';

    protected function configure()
    {
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Local server uses this host', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Local server uses this port', 8000)
            ->addOption('stage', null, InputOption::VALUE_REQUIRED, 'Name of the Phial stage for the local server to use.', DEFAULT_STAGE_NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return Command::SUCCESS;
    }
}
