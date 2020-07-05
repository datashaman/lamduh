<?php

namespace Datashaman\Phial\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NewProjectCommand extends Command
{
    protected static $defaultName = 'new-project';

    protected function configure()
    {
        $this
            ->addArgument('project-name', InputArgument::REQUIRED)
            ->addOption('profile', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectName = $input->getArgument('project-name');
        $profile = $input->getOption('profile');

        if (is_dir($projectName)) {
            $output->writeln("<error>Directory already exists: $projectName</error>");

            return Command::FAILURE;
        }

        $phialDir = $projectName . DIRECTORY_SEPARATOR . '.phial';

        if (!mkdir($phialDir, 0777, true)) {
            $output->writeln("<error>Unable to make directory: $projectName</error>");

            return Command::FAILURE;
        }

        if ($profile) {
            $cfg['profile'] = $profile;
        }

        file_put_contents(
            $phialDir . DIRECTORY_SEPARATOR . 'config.php',
            sprintf(
                TEMPLATE_CONFIG,
                CONFIG_VERSION,
                $projectName,
                DEFAULT_STAGE_NAME,
                DEFAULT_APIGATEWAY_STAGE_NAME
            )
        );

        file_put_contents(
            $projectName . DIRECTORY_SEPARATOR . 'app.php',
            sprintf(TEMPLATE_APP, $projectName) . "\n"
        );

        file_put_contents(
            $projectName . DIRECTORY_SEPARATOR . 'composer.json',
            sprintf(TEMPLATE_COMPOSER, $projectName) . "\n"
        );

        chdir($projectName);

        `composer install`;

        return Command::SUCCESS;
    }
}
