<?php

namespace Datashaman\Phial\Emitter;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

class ConditionalEmitter implements EmitterInterface
{
    private EmitterInterface $emitter;

    public function __construct(EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    public function emit(ResponseInterface $response) : bool
    {
        if (! $response->hasHeader('Content-Disposition')
            && ! $response->hasHeader('Content-Range')
        ) {
            return false;
        }

        return $this->emitter->emit($response);
    }
}
