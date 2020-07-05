<?php

namespace Datashaman\Phial\Command;

use Datashaman\Phial\Deployer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{
    protected static $defaultName = 'deploy';

    protected function configure()
    {
        $this
            ->addOption('autogen-policy', null, InputOption::VALUE_NONE, 'Automatically generate IAM policy for app code.')
            ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Override profile at deploy time.')
            ->addOption('api-gateway-stage', null, InputOption::VALUE_REQUIRED, 'Name of the API gateway stage to deploy to.')
            ->addOption('stage', null, InputOption::VALUE_REQUIRED, 'Name of the Phial stage to deploy to. Specifying a new phial stage will create an entirely new set of AWS resources.', DEFAULT_STAGE_NAME)
            ->addOption('connection-timeout', null, InputOption::VALUE_REQUIRED, 'Overrides the default connection timeout.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deployer = new Deployer(
            $input->getOption('project-dir'),
            $input->getOption('debug'),
            $input->getOption('profile')
        );

        return Command::SUCCESS;
    }
}
