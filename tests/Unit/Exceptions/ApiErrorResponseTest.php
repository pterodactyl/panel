<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Exceptions\ApiErrorResponseTest;

use Pterodactyl\Exceptions\ApiErrorResponse;
use Pterodactyl\Tests\TestCase;
use RuntimeException;
use Throwable;

uses(TestCase::class);
test('exception details are hidden when debugging is disabled', function () {
    config()->set('app.debug', false);
    $error = ApiErrorResponse::toArray(new RuntimeException('sensitive detail'))['errors'][0];
    expect($error['code'])->toBe('RuntimeException');
    expect($error['status'])->toBe('500');
    expect($error['detail'])->toBe('An unexpected error was encountered while processing this request, please try again.');
    $this->assertArrayNotHasKey('source', $error);
    $this->assertArrayNotHasKey('meta', $error);
});
test('debug traces remove arguments and use each previous exception trace', function () {
    config()->set('app.debug', true);
    $error = ApiErrorResponse::toArray(nestedException())['errors'][0];
    $trace = $error['meta']['trace'];
    $previousTrace = $error['meta']['previous'][0];
    expect($trace)->not->toBeEmpty();
    expect($previousTrace)->not->toBeEmpty();
    expect($previousTrace[0]['function'])->toBe(__NAMESPACE__.'\\throwInnerException');
    foreach (array_merge($trace, $previousTrace) as $frame) {
        $this->assertArrayNotHasKey('args', $frame);
        $this->assertArrayNotHasKey('object', $frame);
    }
});
function nestedException(): Throwable
{
    try {
        throwInnerException();
    } catch (Throwable $previous) {
        try {
            throw new RuntimeException('outer', previous: $previous);
        } catch (Throwable $exception) {
            return $exception;
        }
    }
}
function throwInnerException(): never
{
    throw new RuntimeException('inner');
}
