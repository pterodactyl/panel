<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Nodes\UpdatesNodes;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateNode implements UpdatesNodes
{
    public function __construct(private ExtensionFields $extensions) {}

    public function update(Node $node, array $input, bool $resetToken = false): Node
    {
        $validated = Validator::validate($input, CreateNode::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // The description, flags and upload size it is not given keep their stored values.
        $attributes = [
            ...Arr::except($validated, ['daemon_listen', 'daemon_sftp', 'daemon_base']),
            'daemonListen' => $validated['daemon_listen'],
            'daemonSFTP' => $validated['daemon_sftp'],
            // A node given no daemon base keeps its files in /var/lib/pterodactyl/volumes.
            'daemonBase' => $validated['daemon_base'] ?? '/var/lib/pterodactyl/volumes',
        ];

        if ($resetToken) {
            $attributes['daemon_token'] = Crypt::encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
            $attributes['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);
        }

        [$updated, $exception] = DB::transaction(function () use ($attributes, $node, $extensions): array {
            // Wings is reached with the pre-update scheme and port, but at the newly provided
            // FQDN: if the node was pointed at a "valid" FQDN that is not actually running
            // Wings, the operator can still change it back. Only the Panel uses the FQDN for
            // connecting; the node itself does not care about it.
            //
            // @see https://github.com/pterodactyl/panel/issues/1931
            $daemon = clone $node;

            $node->forceFill($attributes)->save();
            $this->extensions->save($node, $extensions);
            // A location loaded before the save may no longer match location_id.
            $node->unsetRelation('location');

            try {
                $daemon->fqdn = $node->fqdn;

                Daemon::node($daemon)->update($node);
            } catch (DaemonConnectionException $daemonConnectionException) {
                Log::warning($daemonConnectionException->getMessage(), ['exception' => $daemonConnectionException, 'node_id' => $node->id]);

                // Never actually throw these exceptions up the stack. If we were able to change the settings
                // but something went wrong with Wings we just want to store the update and let the user manually
                // make changes as needed.
                //
                // This avoids issues with proxies such as Cloudflare which will see Wings as offline and then
                // inject their own response pages, causing this logic to get fucked up.
                //
                // @see https://github.com/pterodactyl/panel/issues/2712
                return [$node, true];
            }

            return [$node, false];
        });

        if ($exception) {
            throw new ConfigurationNotPersistedException(trans('exceptions.node.daemon_off_config_updated'));
        }

        return $updated;
    }
}
