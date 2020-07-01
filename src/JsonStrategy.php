<?php

namespace Datashaman\Phial;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use Throwable;

class JsonStrategy extends \League\Route\Strategy\JsonStrategy
{
    private App $_app;

    public function app($app = null)
    {
        if (func_num_args() === 0) {
            return $this->_app;
        }

        $this->_app = $app;

        return $this;
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->getContainer();
        $container->set(ServerRequestInterface::class, $request);

        $controller = $route->getCallable($container);
        $response = $container->call($controller, $route->getVars());

        if ($this->isJsonEncodable($response)) {
            $body     = json_encode($response, $this->jsonFlags);
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($body);
        }

        $response = $this->applyDefaultResponseHeaders($response);

        return $response;
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class($this->responseFactory->createResponse(), $this->_app) implements MiddlewareInterface
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

                    if ($this->app->debug()) {
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
