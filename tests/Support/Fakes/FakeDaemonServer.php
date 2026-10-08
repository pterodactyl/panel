<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonServer extends FakeDaemonHttpClient
{
    /** @var array<string, mixed> */
    public array $details = [];

    /** @var list<string>|null */
    public ?array $logs = [];

    public function assertDeleted(): void
    {
        Assert::assertNotEmpty($this->callsFor('delete'), 'Expected daemon server delete to have been called.');
    }

    public function assertDeletedTimes(int $times): void
    {
        Assert::assertCount($times, $this->callsFor('delete'), sprintf('Expected daemon server delete to have been called %d time(s).', $times));
    }

    public function assertCreated(): void
    {
        Assert::assertNotEmpty($this->callsFor('create'), 'Expected daemon server create to have been called.');
    }

    public function assertCreatedWith(bool $startOnCompletion): void
    {
        $calls = $this->callsFor('create');

        Assert::assertNotEmpty($calls, 'Expected daemon server create to have been called.');

        foreach ($calls as $call) {
            if (($call['startOnCompletion'] ?? null) === $startOnCompletion) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon server create to have been called with startOnCompletion=%s.', var_export($startOnCompletion, true)));
    }

    public function assertSynced(): void
    {
        Assert::assertNotEmpty($this->callsFor('sync'), 'Expected daemon server sync to have been called.');
    }

    public function assertSyncedTimes(int $times): void
    {
        Assert::assertCount($times, $this->callsFor('sync'), sprintf('Expected daemon server sync to have been called %d time(s).', $times));
    }

    public function assertReinstalled(): void
    {
        Assert::assertNotEmpty($this->callsFor('reinstall'), 'Expected daemon server reinstall to have been called.');
    }

    public function assertDetailsFetched(): void
    {
        Assert::assertNotEmpty($this->callsFor('getDetails'), 'Expected daemon server details to have been fetched.');
    }

    public function assertLogsRead(int $lines): void
    {
        foreach ($this->callsFor('getLogs') as $call) {
            if (($call['size'] ?? null) === (string) $lines) {
                return;
            }
        }

        Assert::fail(sprintf('Expected %d daemon server log line(s) to have been requested.', $lines));
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        $path = parse_url($request->url(), PHP_URL_PATH);
        if ($path === '/api/servers' && $request->method() === 'POST') {
            $this->record('create', ['startOnCompletion' => $request['start_on_completion'], 'server_uuid' => $request['uuid']]);

            return $this->reply();
        }

        if (! preg_match('#^/api/servers/([^/]+)(?:/(sync|reinstall|logs))?$#', $path, $matches)) {
            return null;
        }

        $method = match ([$request->method(), $matches[2] ?? '']) {
            ['GET', ''] => 'getDetails',
            ['DELETE', ''] => 'delete',
            ['POST', 'sync'] => 'sync',
            ['POST', 'reinstall'] => 'reinstall',
            ['GET', 'logs'] => 'getLogs',
            default => null,
        };
        if ($method === null) {
            return null;
        }

        $this->record($method, ['server_uuid' => $matches[1], 'size' => $this->query($request)['size'] ?? null]);

        return $this->reply(match ($method) {
            'getDetails' => $this->details,
            'getLogs' => ['data' => $this->logs],
            default => '',
        });
    }
}
