<?php

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new App('routing2');

$app->route('GET', '/users/{name}', fn(Request $req, array $args) => $args);
