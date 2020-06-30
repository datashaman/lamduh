<?php

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;

(new App('routing1', false))
    ->route('GET', '/', fn () => ['view' => 'index'])
    ->route('GET', '/a', fn () => ['view' => 'a'])
    ->route('GET', '/b', fn () => ['view' => 'b'])
    ->route(
        'GET',
        '/error',
        function () {
            throw new Exception('Something bad happened');
        }
    );
