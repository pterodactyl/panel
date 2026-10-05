<?php

declare(strict_types=1);

namespace Pterodactyl\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Pterodactyl\Tests\Support\OpenApiSpecValidator;

abstract class TestCase extends BaseTestCase
{
    private ?string $lastMatchedRoute = null;

    /**
     * Setup tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // When ROUTE_COVERAGE_FILE is set, append every route the suite dispatches so
        // tooling can diff the hit list against `route:list` for endpoint coverage.
        if ($file = env('ROUTE_COVERAGE_FILE')) {
            Event::listen(RouteMatched::class, function (RouteMatched $event) use ($file) {
                file_put_contents(
                    $file,
                    implode('|', $event->route->methods()).' /'.mb_ltrim($event->route->uri(), '/').PHP_EOL,
                    FILE_APPEND | LOCK_EX,
                );
            });
        }

        // When OPENAPI_VALIDATE is set, every JSON success response the suite produces
        // is checked against the generated OpenAPI spec, turning contract drift into
        // test failures. Requires a freshly generated storage spec (composer docs:openapi).
        if (env('OPENAPI_VALIDATE') && OpenApiSpecValidator::available()) {
            Event::listen(RouteMatched::class, function (RouteMatched $event) {
                $this->lastMatchedRoute = $event->route->uri();
            });
        }

        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        Str::createRandomStringsNormally();
        Str::createUuidsNormally();

        $now = Carbon::now()->startOfSecond();

        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);

        // Why, you ask? If we don't force this to false it is possible for certain exceptions
        // to show their error message properly in the integration test output, but not actually
        // be setup correctly to display their message in production.
        //
        // If we expect a message in a test, and it isn't showing up (rather, showing the generic
        // "an error occurred" message), we can probably assume that the exception isn't one that
        // is recognized as being user viewable.
        config()->set('app.debug', false);

        $this->setKnownUuidFactory();
    }

    /**
     * Tear down tests.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }

    /**
     * Handles the known UUID handling in certain unit tests. Use the "KnownUuid" trait
     * in order to enable this ability.
     */
    public function setKnownUuidFactory(): void
    {
        // do nothing
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null): TestResponse
    {
        $this->lastMatchedRoute = null;

        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        if (env('OPENAPI_VALIDATE') && $this->lastMatchedRoute !== null && OpenApiSpecValidator::available()) {
            $errors = OpenApiSpecValidator::validateResponse(
                $method,
                $this->lastMatchedRoute,
                $response->getStatusCode(),
                (string) $response->getContent(),
            );

            $this->assertSame([], $errors, sprintf(
                "Response for [%s /%s] violates the OpenAPI contract:\n  - %s",
                mb_strtoupper($method),
                mb_ltrim($this->lastMatchedRoute, '/'),
                implode("\n  - ", $errors),
            ));
        }

        return $response;
    }
}
