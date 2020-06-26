<?php

require_once 'vendor/autoload.php';

use Datashaman\Lamduh\App;

$app = new App('routing1');

$app->route('GET', '/', fn () => ['view' => 'index']);
$app->route('GET', '/a', fn () => ['view' => 'a']);
$app->route('GET', '/b', fn () => ['view' => 'b']);
