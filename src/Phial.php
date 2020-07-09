<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use DI\ContainerBuilder;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class Phial
{
    /**
     * @var string
     */
    private $appName;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param string $appName
     * @param ?LoggerInterface $logger
     */
    public function __construct(
        string $appName,
        ?LoggerInterface $logger = null
    ) {
        $this->appName = $appName;
        $this->registerErrorHandler();
        $this->container = $this->buildContainer();
        $this->container->set(
            LoggerInterface::class,
            $logger ? $logger : $this->container->make(
                LoggerInterface::class,
                [
                    'appName' => $appName,
                ]
            )
        );
    }

    public function __destruct()
    {
        $this->container->call([$this, 'run']);
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

    public function deploy(
        ?bool $autogenPolicy = null,
        ?string $profile = null,
        ?string $apiGatewayStage = null,
        ?string $stage = DEFAULT_STAGE_NAME,
        ?int $connectionTimeout = null
    ) {
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
        $this
            ->container
            ->get(RequestHandlerInterface::class)
            ->map($methods, $path, $view);

        return $this;
    }

    public function run(
        ServerRequestInterface $serverRequest,
        RequestHandlerInterface $requestHandler,
        EmitterInterface $emitter
    ): void {
        $emitter->emit($requestHandler->dispatch($serverRequest));
    }

    private function buildContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . '/../config.php');

        return $containerBuilder->build();
    }

    private function registerErrorHandler(): void
    {
        error_reporting(
            E_ALL &
            ~E_USER_DEPRECATED &
            ~E_DEPRECATED &
            ~E_STRICT &
            ~E_NOTICE
        );

        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->register();
    }
}
