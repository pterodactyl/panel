<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Integration;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Tests\Assertions\AssertsActivityLogged;
use Pterodactyl\Tests\TestCase;
use Pterodactyl\Tests\Traits\Integration\CreatesTestModels;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;

abstract class IntegrationTestCase extends TestCase
{
    use AssertsActivityLogged;
    use CreatesTestModels;

    protected array $connectionsToTransact = ['mysql'];

    protected $defaultHeaders = [
        'Accept' => 'application/json',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake(ActivityLogged::class);

        // The React shell reads the built Vite manifest; point the asset service at a
        // committed fixture so view rendering does not depend on a frontend build.
        $this->app->bind(AssetHashService::class, fn (Application $app): AssetHashService => new AssetHashService(
            $app->make(FilesystemManager::class),
            base_path('tests/Fixtures'),
        ));
    }

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     */
    protected function formatTimestamp(CarbonInterface|string $timestamp): string
    {
        $value = $timestamp instanceof CarbonInterface
            ? CarbonImmutable::instance($timestamp)
            : CarbonImmutable::createFromFormat(CarbonInterface::DEFAULT_TO_STRING_FORMAT, $timestamp);

        return $value->setTimezone(BaseTransformer::RESPONSE_TIMEZONE)->toAtomString();
    }
}
