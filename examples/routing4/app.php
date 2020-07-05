<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Datashaman\Phial\Phial;
use Psr\Http\Message\ServerRequestInterface;

$app = new Phial('routing4');

$app->route(
    'GET',
    '/users/{name}',
    function (ServerRequestInterface $req, $name) {
        $params = $req->getQueryParams();

        $result = ['name' => $name];

        if (($params['include-greeting'] ?? '') === 'true') {
            $result['greeting'] = "Hello, ${name}";
        }

        return $result;
    }
);
