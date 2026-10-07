<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Nodes\UpdatesNodes;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Throwable;

final readonly class UpdateNode implements UpdatesNodes
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Update the configuration values for a given node on the machine.
     *
     * @param  NodeUpdateData  $data
     *
     * @throws Throwable
     */
    public function update(Node $node, array $data, bool $resetToken = false): Node
    {
        $extensions = $data['extensions'] ?? [];
        unset($data['extensions']);

        if ($resetToken) {
            $data['daemon_token'] = Crypt::encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
            $data['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);
        }

        [$updated, $exception] = DB::transaction(function () use ($data, $node, $extensions): array {
            // Wings is reached with the pre-update scheme and port, but at the newly provided
            // FQDN: if the node was pointed at a "valid" FQDN that is not actually running
            // Wings, the operator can still change it back. Only the Panel uses the FQDN for
            // connecting; the node itself does not care about it.
            //
            // @see https://github.com/pterodactyl/panel/issues/1931
            $daemon = clone $node;

            $node->forceFill($data)->save();
            $this->extensions->save($node, $extensions);
            $updated = $node->refresh();

            try {
                $daemon->fqdn = $updated->fqdn;

                Daemon::node($daemon)->update($updated);
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
                return [$updated, true];
            }

            return [$updated, false];
        });

        if ($exception) {
            throw new ConfigurationNotPersistedException(trans('exceptions.node.daemon_off_config_updated'));
        }

        return $updated;
    }
}
