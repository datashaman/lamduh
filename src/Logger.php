<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Monolog\Handler\StreamHandler;

final class Logger extends \Monolog\Logger
{
    public function __construct(
        string $appName = 'phial',
        string $logPath = 'phial.log',
        int $logLevel = Logger::WARNING
    ) {
        parent::__construct($appName);

        $this->pushHandler(
            new StreamHandler($logPath, $logLevel)
        );
    }
}
