<?php

namespace Datashaman\Lamduh;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;

class App
{
    protected Router $router;
    protected array $routes = [];

    /**
     * @param string|array $methods
     * @param string $path
     * @param callable $view
     */
    public function route($methods = 'GET', string $path, callable $view)
    {
        $this->routes[] = [$methods, $path, $view];
    }

    public function __destruct()
    {
        $router = $this->createRouter();
        $request = $this->createRequest();
        $response = $router->dispatch($request);
        (new SapiEmitter())->emit($response);
    }

    protected function createRouter()
    {
        $responseFactory = new ResponseFactory();
        $strategy = new JsonStrategy($responseFactory);
        $router = (new Router())->setStrategy($strategy);

        foreach ($this->routes as $route) {
            $router->map(...$route);
        }

        return $router;
    }

    protected function createRequest()
    {
        return ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );
    }
}
