<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Backup;

use Mockery\MockInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Permission;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class RestoreBackupTest extends ClientApiIntegrationTestCase
{
    private MockInterface $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->mock(DaemonBackupRepository::class);
    }

    public function testBackupCanBeRestored()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_BACKUP_RESTORE]);

        /** @var Backup $backup */
        $backup = Backup::factory()->create(['server_id' => $server->id]);

        $this->repository->expects('setServer->restore')->with(
            \Mockery::on(function ($value) use ($backup) {
                return $value instanceof Backup && $value->uuid === $backup->uuid;
            }),
            null,
            true,
        )->andReturn(new GuzzleResponse());

        $this->actingAs($user)->postJson($this->link($backup, 'restore'), ['truncate' => true])
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidBackupDataProvider')]
    public function testBackupCannotBeRestoredUntilSuccessfulAndComplete(bool $isSuccessful, bool $isCompleted)
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_BACKUP_RESTORE]);

        /** @var Backup $backup */
        $backup = Backup::factory()->create([
            'server_id' => $server->id,
            'is_successful' => $isSuccessful,
            'completed_at' => $isCompleted ? CarbonImmutable::now() : null,
        ]);

        $this->repository->shouldNotReceive('setServer');

        $this->actingAs($user)->postJson($this->link($backup, 'restore'), ['truncate' => true])
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public static function invalidBackupDataProvider(): array
    {
        return [
            'failed completed' => [false, true],
            'failed incomplete' => [false, false],
            'successful incomplete' => [true, false],
        ];
    }
}
