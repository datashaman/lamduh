<?php

declare(strict_types=1);

namespace Datashaman\Phial\Handler;

class TestHandler
{
    public function __invoke(array $event)
    {
        return json_encode([
            'event' => $event,
        ]);
    }
}
