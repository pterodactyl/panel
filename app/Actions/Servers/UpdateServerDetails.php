<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Throwable;

final readonly class UpdateServerDetails implements UpdatesServerDetails
{
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

            // Revoke the previous owner's Wings SFTP token after an ownership change.
            if (! $server->refresh()->user->is($original)) {
                dispatch(new RevokeSftpAccessJob($original->uuid, $server));
            }

            return $server->refresh();
        });
    }
}
