<?php

namespace Pterodactyl\Tests\Integration;

use Carbon\CarbonImmutable;
use Pterodactyl\Tests\TestCase;
use Pterodactyl\Tests\Traits\Integration\CreatesTestModels;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;

abstract class IntegrationTestCase extends TestCase
{
    use CreatesTestModels;

    protected array $connectionsToTransact = ['mysql'];

    protected $defaultHeaders = [
        'Accept' => 'application/json',
    ];

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     */
    protected function formatTimestamp(string $timestamp): string
    {
        return CarbonImmutable::createFromFormat(CarbonImmutable::DEFAULT_TO_STRING_FORMAT, $timestamp)
            ->setTimezone(BaseTransformer::RESPONSE_TIMEZONE)
            ->toIso8601String();
    }
}
