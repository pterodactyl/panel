<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Helpers\SoftwareVersionServiceTest;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('normalizes version service response fields', function () {
    $service = service(['panel' => '2.1.0', 'wings' => '1.12.0', 'discord' => 'https://example.com/discord', 'donations' => 'https://example.com/donate']);
    expect($service->getPanel())->toBe('2.1.0');
    expect($service->getDaemon())->toBe('1.12.0');
    expect($service->getDiscord())->toBe('https://example.com/discord');
    expect($service->getDonations())->toBe('https://example.com/donate');
});
test('ignores version fields with invalid types', function () {
    $service = service(['panel' => ['invalid'], 'wings' => '1.12.0']);
    expect($service->getPanel())->toBe('error');
    expect($service->getDaemon())->toBe('1.12.0');
});
/** @param array<string, JsonValue> $payload */
function service(array $payload): SoftwareVersionService
{
    $cache = new Repository(new ArrayStore);
    $client = new Client(['handler' => HandlerStack::create(new MockHandler([
        new Response(200, body: json_encode($payload, JSON_THROW_ON_ERROR)),
    ]))]);

    return new SoftwareVersionService($cache, $client);
}
