<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\ExtensionFileRouteTest;

use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Http\Controllers\Base\ExtensionFileController;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

const NAME = '0123456789abcdef0123456789abcdef01234567.svg';

test('the file route is stateless and only matches server generated names', function (): void {
    $route = Route::getRoutes()->getByName('extensions.files');
    expect($route)->not->toBeNull();
    expect($this->app->make(Router::class)->gatherRouteMiddleware($route))->toBe([], 'no session, cookies or authentication on a publicly cached response');
    expect($this->app->make(Router::class)->gatherRouteMiddleware(Route::getRoutes()->getByName('index')))->toContain(StartSession::class);

    $matches = fn (string $path): bool => $route->matches(request()->create($path));
    expect($matches('/extension-files/probe/'.NAME))->toBeTrue();
    expect($matches('/extension-files/probe/logo.svg'))->toBeFalse();
    expect($matches('/extension-files/Probe/'.NAME))->toBeFalse();
    expect($matches('/extension-files/probe/nested/'.NAME))->toBeFalse();
});

test('stored files are served with their stored type and hardened headers', function (): void {
    config(['extensions.files_disk' => 'local']);
    Storage::fake('local');
    Storage::disk('local')->put('extension-files/probe/'.NAME, '<svg xmlns="http://www.w3.org/2000/svg"/>');

    $response = (new ExtensionFileController)(new ExtensionSettingFiles, 'probe', NAME);

    expect($response->getContent())->toBe('<svg xmlns="http://www.w3.org/2000/svg"/>');
    expect($response->headers->get('Content-Type'))->toBe('image/svg+xml');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Content-Security-Policy'))->toBe("default-src 'none'; style-src 'unsafe-inline'; sandbox");
    expect($response->headers->get('Cache-Control'))->toContain('immutable')->toContain('public')->toContain('max-age=31536000');
});

test('missing files are not found', function (): void {
    config(['extensions.files_disk' => 'local']);
    Storage::fake('local');

    expect(fn () => (new ExtensionFileController)(new ExtensionSettingFiles, 'probe', NAME))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});
