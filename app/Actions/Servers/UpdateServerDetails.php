<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Throwable;

final readonly class UpdateServerDetails implements UpdatesServerDetails
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * A server's details: its external id, name, owner and description, checked as they
     * are when a server is created. Pass the server being updated so its own external id
     * is not taken for a duplicate.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Server $server = null): array
    {
        $rules = CreateServer::rules($server);

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'owner_id' => $rules['owner_id'],
            'description' => $rules['description'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function update(Server $server, array $input): Server
    {
        $validated = Validator::validate($input, self::rules($server));
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // Every detail is replaced: a blank external id is cleared and a blank description is empty.
        $attributes = ['external_id' => null, 'description' => '', ...array_filter($validated, filled(...))];

        $name = $server->name;
        $description = $server->description;

        $server = DB::transaction(function () use ($server, $attributes, $extensions): Server {
            $original = $server->user;
            $server->forceFill($attributes)->saveOrFail();
            $this->extensions->save($server, $extensions);

            // Revoke the previous owner's Wings SFTP token after an ownership change.
            if ($server->owner_id !== $original->id) {
                $job = new RevokeSftpAccessJob($original->uuid, $server);
                DB::afterCommit(fn (): PendingDispatch => dispatch($job));
            }

            return $server->refresh();
        });

        if ($name !== $server->name) {
            Activity::event('server:settings.rename')
                ->subject($server)
                ->property(['old' => $name, 'new' => $server->name])
                ->log();
        }

        if ($description !== $server->description) {
            Activity::event('server:settings.description')
                ->subject($server)
                ->property(['old' => $description, 'new' => $server->description])
                ->log();
        }

        return $server;
    }
}
