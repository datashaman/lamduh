<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;

$app = new App('routing3');

$app->route('GET', '/a/{first}/b/{second}', fn ($first, $second) => ['first' => $first, 'second' => $second]);
