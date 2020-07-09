<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    include_once __DIR__ . '/vendor/autoload.php';
}

use DI\ContainerBuilder;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;

class RuntimeHandler
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $api;

    /**
     * @var string
     */
    private $handler;

    /**
     * @var string
     */
    private $root;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $requestId = '';

    public function __construct()
    {
        $this->api = getenv('AWS_LAMBDA_RUNTIME_API');
        $this->handler = getenv('_HANDLER');
        $this->root = getenv('LAMBDA_TASK_ROOT');

        error_reporting(
            E_ALL &
            ~E_USER_DEPRECATED &
            ~E_DEPRECATED &
            ~E_STRICT &
            ~E_NOTICE
        );

        if (file_exists($this->root . '/vendor/autoload.php')) {
            include_once $this->root . '/vendor/autoload.php';
        }

        $containerBuilder = new ContainerBuilder();

        if (file_exists(__DIR__ . '/config.php')) {
            $containerBuilder->addDefinitions(__DIR__ . '/config.php');
        }

        if (file_exists($this->root . '/config.php')) {
            $containerBuilder->addDefinitions($this->root . '/config.php');
        }

        $this->container = $containerBuilder->build();
    }

    public function handle()
    {
        $this->init();
        $this->processLoop();
    }

    private function init()
    {
        try {
            $this->client = new Client(
                [
                    'base_uri' => "http://{$this->api}/2018-06-01/",
                ]
            );
        } catch (Throwable $exception) {
            $this->postError($exception);
        }
    }

    private function processLoop()
    {
        while (true) {
            try {
                $event = $this->getNextInvocation();
                $context = [];
                $response = $this->container->make(
                    $this->handler,
                    [
                        'event' => $event,
                    ]
                );
                $this->postResponse($response);
            } catch (Throwable $exception) {
                $this->postError($exception);
            }
        }
    }

    private function getNextInvocation(): array
    {
        $response = $this->client->get('runtime/invocation/next');
        $this->requestId = $response->getHeader('lambda-runtime-aws-request-id')[0];

        return json_decode($response->getBody(), true);
    }

    private function postResponse($response): void
    {
        $this->client->post("runtime/invocation/{$this->requestId}/response", ['body' => $response]);
    }

    private function postError(Throwable $exception)
    {
        $response = json_encode(
            [
                'errorMessage' => $exception->getMessage() . ' ' . $exception->getFile() . ':' . $exception->getLine(),
                'errorType' => get_class($exception),
            ]
        );

        $path = $this->requestId
            ? "runtime/invocation/{$this->requestId}/error"
            : 'runtime/init/error';

        $this->client->post(
            $path,
            [
                'body' => $response,
                'headers' => [
                    'Lambda-Runtime-Function-Error-Type' => 'Unhandled',
                ],
            ]
        );

        if (!$this->requestId) {
            exit(1);
        }
    }
}

(new RuntimeHandler())->handle();
