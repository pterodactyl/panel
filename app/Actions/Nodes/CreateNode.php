<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

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
     * A node's fields, for creating one and for updating one. The daemon fields go by their
     * API names; attributes() maps them onto the daemonListen, daemonSFTP and daemonBase columns.
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

    /**
     * Validates node input and returns the model attributes it describes. A node given no
     * daemon base keeps its files in /var/lib/pterodactyl/volumes. The description, flags and
     * upload size are left out when they are not given, so a new node takes the column defaults
     * and an updated one keeps its stored values; a blank description is cleared.
     *
     * @param  ActionInput  $input
     * @return array{name: string, location_id: int, fqdn: string, scheme: string, memory: int, memory_overallocate: int, disk: int, disk_overallocate: int, daemonListen: int, daemonSFTP: int, daemonBase: string, description?: string|null, public?: bool, behind_proxy?: bool, maintenance_mode?: bool, upload_size?: int}
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        $attributes = [
            'name' => $input->string('name')->toString(),
            'location_id' => $input->integer('location_id'),
            'fqdn' => $input->string('fqdn')->toString(),
            'scheme' => $input->string('scheme')->toString(),
            'memory' => $input->integer('memory'),
            'memory_overallocate' => $input->integer('memory_overallocate'),
            'disk' => $input->integer('disk'),
            'disk_overallocate' => $input->integer('disk_overallocate'),
            'daemonListen' => $input->integer('daemon_listen'),
            'daemonSFTP' => $input->integer('daemon_sftp'),
            'daemonBase' => $input->filled('daemon_base') ? $input->string('daemon_base')->toString() : '/var/lib/pterodactyl/volumes',
        ];

        if ($input->has('description')) {
            $attributes['description'] = $input->filled('description') ? $input->string('description')->toString() : null;
        }

        if ($input->has('public')) {
            $attributes['public'] = $input->boolean('public');
        }

        if ($input->has('behind_proxy')) {
            $attributes['behind_proxy'] = $input->boolean('behind_proxy');
        }

        if ($input->has('maintenance_mode')) {
            $attributes['maintenance_mode'] = $input->boolean('maintenance_mode');
        }

        if ($input->filled('upload_size')) {
            $attributes['upload_size'] = $input->integer('upload_size');
        }

        return $attributes;
    }

    /**
     * Create a node and mint its daemon token pair.
     *
     * @param  ActionInput  $input
     */
    public function create(array $input): Node
    {
        $attributes = self::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($attributes, $extensions): Node {
            $node = Node::query()->forceCreate([
                ...$attributes,
                'daemon_token' => Crypt::encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH)),
                'daemon_token_id' => Str::random(Node::DAEMON_TOKEN_ID_LENGTH),
            ]);
            $this->extensions->save($node, $extensions);

            return $node->refresh();
        });
    }
}
