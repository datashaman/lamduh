<?php

namespace Datashaman\Phial;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

class App extends Router
{
    public bool $debug;

    protected bool $hasRun = false;

    public function __construct(
        string $appName,
        bool $debug = false,
        ?RouteParser $parser = null,
        ?DataGenerator $generator = null
    ) {
        $this->appName = $appName;
        $this->debug = $debug;

        parent::__construct($parser, $generator);
    }

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
        $strategy->app = $this;
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
