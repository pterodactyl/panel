<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;
use Throwable;

final readonly class UpdateServerDetails implements UpdatesServerDetails
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * @param  ModelAttributes  $data
     *
     * @throws Throwable
     */
    public function update(Server $server, array $data): Server
    {
        return DB::transaction(function () use ($data, $server): Server {
            $original = $server->user;
            $server->forceFill([
                'external_id' => Arr::get($data, 'external_id'),
                'owner_id' => Arr::get($data, 'owner_id'),
                'name' => Arr::get($data, 'name'),
                'description' => Arr::get($data, 'description') ?? '',
            ])->saveOrFail();
            // SAFETY: requests put the validated values of each extension under `extensions`.
            $this->extensions->save($server, ExtensionSettingValueGuard::fieldInput($data['extensions'] ?? []));

            // Revoke the previous owner's Wings SFTP token after an ownership change.
            if (! $server->refresh()->user->is($original)) {
                dispatch(new RevokeSftpAccessJob($original->uuid, $server));
            }

            return $server->refresh();
        });
    }
}
