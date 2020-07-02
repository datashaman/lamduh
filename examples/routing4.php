<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Datashaman\Phial\App;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new App('routing4');

$app->route(
    'GET',
    '/users/{name}',
    function (Request $req, $name) {
        $params = $req->getQueryParams();

        $result = ['name' => $name];

        if (($params['include-greeting'] ?? '') === 'true') {
            $result['greeting'] = "Hello, ${name}";
        }

        return $result;
    }
);
