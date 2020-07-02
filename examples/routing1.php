<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;
use Psr\Log\LoggerInterface;

(new App('routing1'))
    ->debug(true)
    ->route('GET', '/', fn () => ['view' => 'index'])
    ->route('GET', '/a', fn () => ['view' => 'a'])
    ->route('GET', '/b', fn () => ['view' => 'b'])
    ->route(
        'GET',
        '/error',
        function (LoggerInterface $logger): void {
            $logger->warning('YOYO');
            throw new Exception('Something bad happened');
        }
    );
