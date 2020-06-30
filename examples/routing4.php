<?php

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new App('routing4');

$app->route(
    'GET',
    '/users/{name}',
    function (Request $req, array $args) {
        $params = $req->getQueryParams();

        $result = $args;

        if (($params['include-greeting'] ?? '') === 'true') {
            $result['greeting'] = "Hello, {$args['name']}";
        }

        return $result;
    }
);
