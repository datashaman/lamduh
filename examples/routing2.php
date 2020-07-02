<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new App('routing2');

$app->route('GET', '/users/{name}', fn($name) => ['name' => $name]);
