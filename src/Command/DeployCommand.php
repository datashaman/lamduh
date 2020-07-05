<?php

namespace Datashaman\Phial\Command;

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
            ->addOption('stage', null, InputOption::VALUE_REQUIRED, 'Name of the Phial stage to deploy to. Specifying a new phial stage will create an entirely new set of AWS resources.')
            ->addOption('connection-timeout', null, InputOption::VALUE_REQUIRED, 'Overrides the default botocore connection timeout.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ... put here the code to run in your command

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}
