<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use League\Route\Strategy\JsonStrategy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class Strategy extends JsonStrategy
{
    public function invokeRouteCallable(
        Route $route,
        ServerRequestInterface $request
    ): ResponseInterface {
        $container = $this->getContainer();
        $container->set(ServerRequestInterface::class, $request);

        $controller = $route->getCallable($container);
        $response = $container->call($controller, $route->getVars());

        if ($this->isJsonEncodable($response)) {
            $body = json_encode($response, $this->jsonFlags);
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($body);
        }

        return $this->applyDefaultResponseHeaders($response);
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class($this->getContainer(), $this->responseFactory->createResponse()) implements MiddlewareInterface {
            private ContainerInterface $container;
            private ResponseInterface $response;

            public function __construct(
                ContainerInterface $container,
                ResponseInterface $response
            ) {
                $this->container = $container;
                $this->response = $response;
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

                    if ($this->container->get('app.debug')) {
                        throw $exception;
                    }

                    $response->getBody()->write(
                        json_encode(
                            [
                                'status_code' => 500,
                                'reason_phrase' => $exception->getMessage(),
                            ]
                        )
                    );

                    $response = $response->withAddedHeader(
                        'content-type',
                        'application/json'
                    );

                    return $response->withStatus(
                        500,
                        strtok($exception->getMessage(), "\n")
                    );
                }
            }
        };
    }
}
