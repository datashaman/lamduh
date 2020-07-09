<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

return [
    'app.debug' => false,
    'app.name' => 'phial',

    'http.maxBufferLength' => 2048,

    'log.level' => Datashaman\Phial\Logger::DEBUG,
    'log.path' => 'phial.log',

    Laminas\HttpHandlerRunner\Emitter\EmitterInterface::class => DI\create(Datashaman\Phial\Emitter::class)
        ->constructor(
            DI\get('http.maxBufferLength')
        ),

    Psr\Http\Message\ServerRequestInterface::class => function () {
        return Laminas\Diactoros\ServerRequestFactory::fromGlobals();
    },

    Psr\Http\Server\RequestHandlerInterface::class => DI\create(Datashaman\Phial\RequestHandler::class)
        ->constructor(
            DI\get(ContainerInterface::class)
        ),

    Psr\Log\LoggerInterface::class => DI\create(Datashaman\Phial\Logger::class)
        ->constructor(
            DI\get('app.name'),
            DI\get('log.path'),
            DI\get('log.level')
        ),
];
