<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class Emitter extends EmitterStack
{
    public function __construct(int $maxBufferLength = 2048)
    {
        $this->push(new SapiEmitter());
        $this->push(
            new Emitter\ConditionalEmitter(
                new SapiStreamEmitter($maxBufferLength)
            )
        );
    }
}
