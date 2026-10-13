<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Files\FileActivityTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Contracts\Files\DeletesFiles;
use Pterodactyl\Contracts\Files\WritesFileContents;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonFile;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('file changes made without the http api are logged on the server', function (): void {
    new FakeDaemonFile;
    $server = $this->createServerModel();

    $this->app->make(WritesFileContents::class)->write($server, '/server.properties', 'motd=hi');
    $this->app->make(DeletesFiles::class)->delete($server, '/logs', ['latest.log']);

    expect($this->assertActivityOn('server:file.write', $server)->properties->all())->toBe(['file' => '/server.properties'])
        ->and($this->assertActivityOn('server:file.delete', $server)->properties->all())->toBe(['directory' => '/logs', 'files' => ['latest.log']]);
});
