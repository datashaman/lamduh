<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Aws\Exception\AwsException;
use Aws\Sdk;

class Deployer
{
    private bool $debug;
    private Sdk $sdk;
    private ?string $profile;
    private string $projectDir;

    public function __construct(
        string $projectDir,
        bool $debug = false,
        ?string $profile = null
    ) {
        $this->projectDir = $projectDir;
        $this->debug = $debug;
        $this->profile = $profile;
        $this->sdk = new Sdk();
    }

    public function deploy()
    {
    }
}
