<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonRevocation extends FakeDaemonHttpClient
{
    /**
     * @param  ?string[]  $servers
     */
    public function assertDeauthorized(string $user, ?array $servers = null): void
    {
        $calls = $this->callsFor('deauthorize');

        Assert::assertNotEmpty($calls, 'Expected daemon deauthorize to have been called.');

        foreach ($calls as $call) {
            if (($call['user'] ?? null) !== $user) {
                continue;
            }

            if ($servers === null || ($call['servers'] ?? null) === $servers) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon deauthorize for user [%s] to have been called.', $user));
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        if (parse_url($request->url(), PHP_URL_PATH) !== '/api/deauthorize-user' || $request->method() !== 'POST') {
            return null;
        }

        $this->record('deauthorize', ['user' => $request['user'], 'servers' => $request['servers']]);

        return $this->reply();
    }
}
