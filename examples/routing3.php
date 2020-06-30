<?php

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new App('routing3');

$app->route('GET', '/a/{first}/b/{second}', fn(Request $req, $args) => $args);
