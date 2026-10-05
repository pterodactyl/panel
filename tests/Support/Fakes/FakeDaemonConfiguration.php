<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonConfiguration extends FakeDaemonHttpClient
{
    /** @var array<string, mixed> */
    public array $systemInformation = [];

    public function assertSystemInformationFetched(): void
    {
        Assert::assertNotEmpty($this->callsFor('getSystemInformation'), 'Expected daemon system information to have been fetched.');
    }

    public function assertUpdated(): void
    {
        Assert::assertNotEmpty($this->callsFor('update'), 'Expected daemon configuration update to have been called.');
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        $path = parse_url($request->url(), PHP_URL_PATH);
        if ($path === '/api/system' && $request->method() === 'GET') {
            $this->record('getSystemInformation', ['version' => $this->query($request)['v'] ?? null]);

            return $this->reply($this->systemInformation);
        }

        if ($path === '/api/update' && $request->method() === 'POST') {
            $this->record('update', ['configuration' => $request->data()]);

            return $this->reply();
        }

        return null;
    }
}
