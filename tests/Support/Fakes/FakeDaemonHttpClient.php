<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class FakeDaemonHttpClient
{
    public ?Throwable $throwable = null;

    public ?ResponseInterface $response = null;

    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public function __construct()
    {
        Http::fake(fn (Request $request): ?PromiseInterface => $this->responseFor($request));
    }

    abstract protected function responseFor(Request $request): ?PromiseInterface;

    /** @return list<array<string, mixed>> */
    public function callsFor(string $method): array
    {
        return array_values(array_filter($this->calls, fn (array $call): bool => $call['method'] === $method));
    }

    public function assertNothingHappened(): void
    {
        Assert::assertSame([], $this->calls, 'Expected no Wings requests to have been made.');
    }

    /** @param array<string, mixed> $arguments */
    protected function record(string $method, array $arguments = []): void
    {
        $this->calls[] = ['method' => $method, ...$arguments];
    }

    /** @param string|array<array-key, mixed> $body */
    protected function reply(string|array $body = ''): PromiseInterface
    {
        if ($this->throwable !== null) {
            throw $this->throwable;
        }

        if ($this->response !== null) {
            return Http::response((string) $this->response->getBody(), $this->response->getStatusCode(), $this->response->getHeaders());
        }

        return Http::response($body);
    }

    /** @return array<string, string> */
    protected function query(Request $request): array
    {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

        return $query;
    }
}
