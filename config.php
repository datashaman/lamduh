<?php

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

return [
    'app.debug' => true,
    'app.name' => 'phial',

    'http.maxBufferLength' => 2048,

    'log.level' => Monolog\Logger::DEBUG,
    'log.path' => 'phial.log',

    Laminas\HttpHandlerRunner\Emitter\EmitterInterface::class => DI\factory(
        function (ContainerInterface $container) {
            $sapiStreamEmitter = new SapiStreamEmitter($container->get('http.maxBufferLength'));

            $conditionalEmitter = new class ($sapiStreamEmitter) implements EmitterInterface {
                private EmitterInterface $emitter;

                public function __construct(EmitterInterface $emitter)
                {
                    $this->emitter = $emitter;
                }

                public function emit(ResponseInterface $response) : bool
                {
                    if (! $response->hasHeader('Content-Disposition')
                        && ! $response->hasHeader('Content-Range')
                    ) {
                        return false;
                    }

                    return $this->emitter->emit($response);
                }
            };

            $stack = new EmitterStack();

            $stack->push(new SapiEmitter());
            $stack->push($conditionalEmitter);

            return $stack;
        }
    ),

    Psr\Log\LoggerInterface::class => DI\factory(
        function (ContainerInterface $container) {
            $logger = new Monolog\Logger($container->get('app.name'));
            $handler = new Monolog\Handler\StreamHandler($container->get('log.path'), $container->get('log.level'));
            $logger->pushHandler($handler);

            return $logger;
        }
    ),

    Psr\Http\Server\RequestHandlerInterface::class => DI\factory(
        function (ContainerInterface $container) {
            $responseFactory = new Laminas\Diactoros\ResponseFactory();
            $strategy = (new Datashaman\Phial\JsonStrategy($responseFactory))->setContainer($container);

            return (new League\Route\Router())->setStrategy($strategy);
        }
    ),
];
