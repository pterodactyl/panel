<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Telemetry;

use Exception;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Telemetry\SendsTelemetry;
use Pterodactyl\Services\Telemetry\TelemetryCollectionService;

final readonly class SendTelemetry implements SendsTelemetry
{
    public function __construct(private TelemetryCollectionService $telemetry) {}

    public function send(): void
    {
        try {
            $data = $this->telemetry->collect();
        } catch (Exception) {
            return;
        }

        try {
            Http::timeout(5)->post('https://telemetry.pterodactyl.io', $data);
        } catch (Exception) {
            return;
        }
    }
}
