<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Datashaman\Phial\Phial;

$app = new Phial('routing3');

$app->route('GET', '/a/{first}/b/{second}', fn ($first, $second) => ['first' => $first, 'second' => $second]);
