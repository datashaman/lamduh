<?php

namespace Datashaman\Lamduh;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use Whoops\Run;
use Whoops\Handler\PlainTextHandler;

class App
{
    protected array $routes = [];
    protected bool $hasRun = false;

    public function route($methods = 'GET', string $path, callable $view): self
    {
        $this->routes[] = [$methods, $path, $view];

        return $this;
    }

    public function run()
    {
        $this->hasRun = true;
        $router = $this->createRouter();
        $request = $this->createRequest();
        $response = $router->dispatch($request);
        (new SapiEmitter())->emit($response);
    }

    public function __destruct()
    {
        $this->hasRun || $this->run();
    }

    protected function createRouter(): Router
    {
        $responseFactory = new ResponseFactory();
        $strategy = new JsonStrategy($responseFactory);
        $router = (new Router())->setStrategy($strategy);

        foreach ($this->routes as $route) {
            $router->map(...$route);
        }

        return $router;
    }

    protected function createRequest(): ServerRequest
    {
        return ServerRequestFactory::fromGlobals();
    }
}
