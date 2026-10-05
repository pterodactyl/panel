<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonCommand extends FakeDaemonHttpClient
{
    /**
     * @param  array<int, string>|string  $command
     */
    public function assertSent(array|string $command): void
    {
        foreach ($this->callsFor('send') as $call) {
            if (($call['command'] ?? null) === (is_array($command) ? $command : [$command])) {
                return;
            }
        }

        Assert::fail('Expected daemon command to have been sent.');
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        if ($request->method() !== 'POST' || ! preg_match('#^/api/servers/([^/]+)/commands$#', parse_url($request->url(), PHP_URL_PATH), $matches)) {
            return null;
        }

        $this->record('send', ['command' => $request['commands'], 'server_uuid' => $matches[1]]);

        return $this->reply();
    }
}
