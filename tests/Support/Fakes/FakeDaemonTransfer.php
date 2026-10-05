<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonTransfer extends FakeDaemonHttpClient
{
    public function assertNotified(): void
    {
        Assert::assertNotEmpty($this->callsFor('notify'), 'Expected daemon transfer notify to have been called.');
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        if ($request->method() !== 'POST' || ! preg_match('#^/api/servers/([^/]+)/transfer$#', parse_url($request->url(), PHP_URL_PATH))) {
            return null;
        }

        $this->record('notify', ['url' => $request['url'], 'token' => $request['token']]);

        return $this->reply();
    }
}
