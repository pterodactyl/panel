<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Console\GenerateKeyWithoutAppKeyTest;

use Illuminate\Support\Facades\Process;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

test('a new installation can generate its application key', function (): void {
    $result = Process::path(base_path())
        ->env(['APP_KEY' => '', 'APP_ENVIRONMENT_ONLY' => 'true', 'CLOCKWORK_ENABLE' => 'false'])
        ->run([PHP_BINARY, 'artisan', 'key:generate', '--show', '--no-ansi']);

    expect($result->exitCode())->toBe(0, $result->errorOutput().$result->output());
    expect(mb_trim($result->output()))->toStartWith('base64:');
});
