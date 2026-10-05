<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonBackup extends FakeDaemonHttpClient
{
    public function assertBackedUp(?string $backupUuid = null): void
    {
        $calls = $this->callsFor('backup');

        Assert::assertNotEmpty($calls, 'Expected daemon backup to have been triggered.');

        if ($backupUuid !== null) {
            foreach ($calls as $call) {
                if (($call['backup_uuid'] ?? null) === $backupUuid) {
                    return;
                }
            }

            Assert::fail(sprintf('Expected daemon backup for [%s] to have been triggered.', $backupUuid));
        }
    }

    public function assertRestored(?string $backupUuid = null): void
    {
        $calls = $this->callsFor('restore');

        Assert::assertNotEmpty($calls, 'Expected daemon backup restore to have been triggered.');

        if ($backupUuid !== null) {
            foreach ($calls as $call) {
                if (($call['backup_uuid'] ?? null) === $backupUuid) {
                    return;
                }
            }

            Assert::fail(sprintf('Expected daemon backup restore for [%s] to have been triggered.', $backupUuid));
        }
    }

    public function assertDeleted(?string $backupUuid = null): void
    {
        $calls = $this->callsFor('delete');

        Assert::assertNotEmpty($calls, 'Expected daemon backup delete to have been called.');

        if ($backupUuid !== null) {
            foreach ($calls as $call) {
                if (($call['backup_uuid'] ?? null) === $backupUuid) {
                    return;
                }
            }

            Assert::fail(sprintf('Expected daemon backup delete for [%s] to have been called.', $backupUuid));
        }
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        if (! preg_match('#^/api/servers/([^/]+)/backup(?:/([^/]+)(/restore)?)?$#', parse_url($request->url(), PHP_URL_PATH), $matches)) {
            return null;
        }

        $method = match ([$request->method(), isset($matches[2]), isset($matches[3])]) {
            ['POST', false, false] => 'backup',
            ['DELETE', true, false] => 'delete',
            ['POST', true, true] => 'restore',
            default => null,
        };
        if ($method === null) {
            return null;
        }

        $this->record($method, [
            'backup_uuid' => $matches[2] ?? ($request->data()['uuid'] ?? null),
            'adapter' => ($request->data()['adapter'] ?? null),
            'url' => ($request->data()['download_url'] ?? null),
            'truncate' => ($request->data()['truncate_directory'] ?? null),
        ]);

        return $this->reply();
    }
}
