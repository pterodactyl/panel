<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Exceptions\Http\Connection\DaemonConnectionExceptionTest;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('reports warning with request context for client errors', function () {
    $handler = new TestHandler();
    Log::swap(new \Illuminate\Log\Logger(new Logger('test', [$handler])));
    $previous = Http::failedRequest(['error' => 'bad request'], 400, ['X-Request-Id' => 'request-123']);
    (new DaemonConnectionException($previous))->report();
    expect($handler->hasRecords(Level::Warning))->toBeTrue();
    expect($handler->hasRecords(Level::Error))->toBeFalse();
    $records = $handler->getRecords();
    expect($records)->toHaveCount(1);
    expect($records[0]->message)->toBe($previous->getMessage());
    expect($records[0]->context['request_id'])->toBe('request-123');
    expect($records[0]->context['exception'])->toBe($previous);
});
test('reports error for server errors', function () {
    $handler = new TestHandler();
    Log::swap(new \Illuminate\Log\Logger(new Logger('test', [$handler])));
    $previous = Http::failedRequest([], 502);
    (new DaemonConnectionException($previous))->report();
    expect($handler->hasRecords(Level::Error))->toBeTrue();
    expect($handler->hasRecords(Level::Warning))->toBeFalse();
    $records = $handler->getRecords();
    expect($records)->toHaveCount(1);
    expect($records[0]->message)->toBe($previous->getMessage());
    expect($records[0]->context['exception'])->toBe($previous);
});
