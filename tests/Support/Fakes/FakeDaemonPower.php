<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonPower extends FakeDaemonHttpClient
{
    public function assertSent(string $action): void
    {
        foreach ($this->callsFor('send') as $call) {
            if (($call['action'] ?? null) === $action) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon power action [%s] to have been sent.', $action));
    }

    public function assertSentTimes(string $action, int $times): void
    {
        $matching = array_values(array_filter(
            $this->callsFor('send'),
            fn (array $call): bool => ($call['action'] ?? null) === $action
        ));

        Assert::assertCount($times, $matching, sprintf('Expected daemon power action [%s] to have been sent %d time(s).', $action, $times));
    }

    public function assertSentTo(string $action, string $serverUuid): void
    {
        foreach ($this->callsFor('send') as $call) {
            if (($call['action'] ?? null) === $action && ($call['server_uuid'] ?? null) === $serverUuid) {
                return;
            }
        }

        Assert::fail(sprintf('Expected power action [%s] for server [%s].', $action, $serverUuid));
    }

    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->callsFor('send'), 'Expected no daemon power action to have been sent.');
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        if ($request->method() !== 'POST' || ! preg_match('#^/api/servers/([^/]+)/power$#', parse_url($request->url(), PHP_URL_PATH), $matches)) {
            return null;
        }

        $this->record('send', ['action' => $request['action'], 'server_uuid' => $matches[1]]);

        return $this->reply();
    }
}
