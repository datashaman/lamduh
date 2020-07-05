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

    protected function export($expression): string
    {
        $export = var_export($expression, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(
            [
                "/\s*array\s\($/",
                "/\)(,)?$/",
                "/\s=>\s$/"
            ],
            [
                null,
                ']$1',
                ' => ['
            ],
            $array
        );

        return join(PHP_EOL, array_filter(["["] + $array));
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

        $cfg = [
            'app_name' => $projectName,
            'stages' => [
                DEFAULT_STAGE_NAME => [
                    'api_gateway_stage' => DEFAULT_APIGATEWAY_STAGE_NAME,
                ],
            ],
            'version' => CONFIG_VERSION,
        ];

        if ($profile) {
            $cfg['profile'] = $profile;
        }

        file_put_contents(
            $phialDir . DIRECTORY_SEPARATOR . 'config.php',
            sprintf(
                TEMPLATE_CONFIG,
                $this->export($cfg)
            ) . PHP_EOL
        );

        file_put_contents(
            $projectName . DIRECTORY_SEPARATOR . 'app.php',
            sprintf(TEMPLATE_APP, $projectName) . PHP_EOL
        );

        file_put_contents(
            $projectName . DIRECTORY_SEPARATOR . 'composer.json',
            sprintf(TEMPLATE_COMPOSER, $projectName) . PHP_EOL
        );

        chdir($projectName);

        `composer install`;

        return Command::SUCCESS;
    }
}
