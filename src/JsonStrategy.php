<?php

namespace Datashaman\Phial;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use League\Route\Http\Exception as HttpException;
use Throwable;

class JsonStrategy extends \League\Route\Strategy\JsonStrategy
{
    public App $app;

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class($this->responseFactory->createResponse(), $this->app) implements MiddlewareInterface
        {
            protected $response;
            protected $app;

            public function __construct(ResponseInterface $response, App $app)
            {
                $this->response = $response;
                $this->app = $app;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ): ResponseInterface {
                try {
                    return $requestHandler->handle($request);
                } catch (Throwable $exception) {
                    $response = $this->response;

                    if ($exception instanceof HttpException) {
                        return $exception->buildJsonResponse($response);
                    }

                    if ($this->app->debug) {
                        throw $exception;
                    }

                    $response->getBody()->write(json_encode([
                        'status_code'   => 500,
                        'reason_phrase' => $exception->getMessage()
                    ]));

                    $response = $response->withAddedHeader('content-type', 'application/json');
                    return $response->withStatus(500, strtok($exception->getMessage(), "\n"));
                }
            }
        };
    }
}
