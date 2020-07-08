<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

class RuntimeHandler
{
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

    /**
     * @var bool
     */
    private $invoked = false;

    public function __construct()
    {
        error_reporting(
            E_ALL &
            ~E_USER_DEPRECATED &
            ~E_DEPRECATED &
            ~E_STRICT &
            ~E_NOTICE
        );
    }

    public function handle()
    {
        $this->init();
        $this->processLoop();
    }

    private function init()
    {
        try {
            $this->api = getenv('AWS_LAMBDA_RUNTIME_API');
            $this->handler = getenv('_HANDLER');
            $this->root = getenv('LAMBDA_TASK_ROOT');

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
                [$requestId, $event] = $this->getNextInvocation();
                $response = json_encode($event, JSON_PRETTY_PRINT);
                $this->postResponse($requestId, $response);
            } catch (Throwable $exception) {
                $this->postError($exception);
            }
        }
    }

    private function getNextInvocation(): array
    {
        $response = $this->client->get('runtime/invocation/next');
        $this->invoked = true;

        return [
            $response->getHeader('lambda-runtime-aws-request-id')[0],
            json_decode($response->getBody(), true),
        ];
    }

    private function postResponse(string $requestId, string $response): void
    {
        $this->client->post("runtime/invocation/$requestId/response", ['body' => $response]);
    }

    private function postError(Throwable $exception)
    {
        $response = json_encode(
            [
                'errorMessage' => $exception->getMessage(),
                'errorType' => get_class($exception),
            ]
        );

        $path = $this->invoked
            ? "runtime/invocation/$requestId/error"
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

        if (!$this->invoked) {
            exit(1);
        }
    }
}

(new RuntimeHandler())->handle();