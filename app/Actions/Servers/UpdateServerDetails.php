<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
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
     * Validates details input for the server and returns the details it describes. Every
     * detail is replaced: an external id left out or empty is cleared, and a description
     * left out or null becomes empty.
     *
     * @param  ActionInput  $input
     * @return array{external_id: string|null, owner_id: int, name: string, description: string}
     */
    public static function attributes(array $input, ?Server $server = null): array
    {
        $input = Validator::make($input, self::rules($server))->safe();

        return [
            'external_id' => $input->filled('external_id') ? $input->string('external_id')->toString() : null,
            'owner_id' => $input->integer('owner_id'),
            'name' => $input->string('name')->toString(),
            'description' => $input->string('description')->toString(),
        ];
    }

    /**
     * @param  ActionInput  $input
     *
     * @throws Throwable
     */
    public function update(Server $server, array $input): Server
    {
        $attributes = self::attributes($input, $server);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($server, $attributes, $extensions): Server {
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
    }
}
