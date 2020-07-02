<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use DI\ContainerBuilder;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class App implements LoggerAwareInterface
{
    private bool $hasRun = false;
    private array $routes = [];

    public function __construct(string $appName)
    {
        $this->appName = $appName;

        error_reporting(
            E_ALL &
            ~E_USER_DEPRECATED &
            ~E_DEPRECATED &
            ~E_STRICT &
            ~E_NOTICE
        );

        $this->registerErrorHandler();
        $this->container = $this->buildContainer();
    }

    public function __destruct()
    {
        if (! $this->hasRun) {
            $this->run();
        }
    }

    /**
     * @param ?bool $debug
     *
     * @return bool|self
     */
    public function debug(?bool $debug = null)
    {
        if (func_num_args() === 0) {
            return $this->container->get('app.debug');
        }

        $this->container->set('app.debug', $debug);

        return $this;
    }

    /**
     * @param string|string[] $methods
     * @param string $path
     * @param callable $view
     *
     * @return self
     */
    public function route($methods, string $path, callable $view): self
    {
        $this->routes[] = [$methods, $path, $view];

        return $this;
    }

    public function run(): void
    {
        $router = $this->container->get(RequestHandlerInterface::class);

        $this->hasRun = true;

        foreach ($this->routes as $route) {
            $router->map(...$route);
        }

        $request = $this->createRequest();
        $response = $router->dispatch($request);

        $emitter = $this->container->get(EmitterInterface::class);
        $emitter->emit($response);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->container->set(LoggerInterface::class, $logger);
    }

    private function buildContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . '/../config.php');

        return $containerBuilder->build();
    }

    private function createRequest(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals();
    }

    private function registerErrorHandler(): void
    {
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->register();
    }
}
