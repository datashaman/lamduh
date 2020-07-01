<?php

namespace Datashaman\Phial;

use DI\Container;
use DI\ContainerBuilder;
use FastRoute\DataGenerator;
use FastRoute\RouteParser;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use League\Route\Router;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

class App
{
    private bool $_debug;
    private bool $hasRun = false;
    private Container $container;

    public function __construct(
        string $appName,
        bool $debug = false,
        LoggerInterface $logger = null
    ) {
        $this->appName = $appName;
        $this->_debug = $debug;

        $this->buildContainer();
        $this->configureLogging($logger);
        $this->registerErrorHandler();
    }

    public function debug($debug = null)
    {
        if (func_num_args() === 0) {
            return $this->_debug;
        }

        $this->_debug = $debug;

        return $this;
    }

    public function route($methods = 'GET', string $path, callable $view): self
    {
        $this->routes[] = [$methods, $path, $view];

        return $this;
    }

    public function run()
    {
        $this->hasRun = true;
        $request = $this->createRequest();
        $response = $this->createRouter()->dispatch($request);
        $this->createEmitter()->emit($response);
    }

    public function __destruct()
    {
        $this->hasRun || $this->run();
    }

    private function buildContainer()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . '/../config.php');
        $this->container = $containerBuilder->build();
    }

    private function configureLogging(?LoggerInterface $logger)
    {
        if (!$logger) {
            $logger = new Logger($this->appName);
            $logger->pushHandler(new StreamHandler($this->container->get('log.path')));
        }

        $this->container->set(LoggerInterface::class, $logger);
    }

    private function createEmitter(int $maxBufferLength = 2048): EmitterInterface
    {
        $sapiStreamEmitter = new SapiStreamEmitter($maxBufferLength);

        $conditionalEmitter = new class ($sapiStreamEmitter) implements EmitterInterface {
            private $emitter;

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

    private function createRequest(): ServerRequest
    {
        return ServerRequestFactory::fromGlobals();
    }

    private function createRouter(): Router
    {
        $responseFactory = new ResponseFactory();
        $strategy = (new JsonStrategy($responseFactory))->setContainer($this->container);
        $strategy->app($this);
        $router = (new Router())->setStrategy($strategy);

        foreach ($this->routes as $route) {
            $router->map(...$route);
        }

        return $router;
    }

    private function registerErrorHandler()
    {
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->register();
    }
}
