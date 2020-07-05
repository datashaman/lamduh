<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Datashaman\Phial\Phial;

$app = new Phial('routing2');

$app->route('GET', '/users/{name}', fn ($name) => ['name' => $name]);
