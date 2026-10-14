<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class CreateNode implements CreatesNodes
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * The daemon fields go by their API names, which map onto the daemonListen, daemonSFTP and
     * daemonBase columns.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'public' => ['boolean'],
            'name' => ['required', 'regex:/^([\w .-]{1,100})$/'],
            'description' => ['string', 'nullable'],
            'location_id' => ['required', 'exists:locations,id'],
            'fqdn' => ['required', 'string'],
            'scheme' => ['required'],
            'behind_proxy' => ['boolean'],
            'maintenance_mode' => ['boolean'],
            'memory' => ['required', 'integer', 'min:1'],
            'memory_overallocate' => ['required', 'integer', 'min:-1'],
            'disk' => ['required', 'integer', 'min:1'],
            'disk_overallocate' => ['required', 'integer', 'min:-1'],
            'upload_size' => ['int', 'min:1'],
            'daemon_listen' => ['required', 'integer', 'between:1,65535'],
            'daemon_sftp' => ['required', 'integer', 'between:1,65535'],
            'daemon_base' => ['sometimes', 'required', 'regex:/^([\/][\d\w.\-\/]+)$/'],
        ];
    }

    public function create(array $input): Node
    {
        $validated = Validator::validate($input, self::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($validated, $extensions): Node {
            $node = Node::query()->forceCreate([
                ...Arr::except($validated, ['daemon_listen', 'daemon_sftp', 'daemon_base']),
                'daemonListen' => $validated['daemon_listen'],
                'daemonSFTP' => $validated['daemon_sftp'],
                // A node given no daemon base keeps its files in /var/lib/pterodactyl/volumes.
                'daemonBase' => $validated['daemon_base'] ?? '/var/lib/pterodactyl/volumes',
                'daemon_token' => Crypt::encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH)),
                'daemon_token_id' => Str::random(Node::DAEMON_TOKEN_ID_LENGTH),
            ]);
            $this->extensions->save($node, $extensions);

            return $node->refresh();
        });
    }
}
