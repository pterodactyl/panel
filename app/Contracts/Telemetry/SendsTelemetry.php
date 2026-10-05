<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Telemetry;

interface SendsTelemetry
{
    public function send(): void;
}
