<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Transfers\InitiatesTransfers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\TransferServerRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class TransferController extends AdminApiController
{
    private const array TRANSFER_NOT_VIABLE_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'This server cannot be transferred to the selected node because the node does not have enough resources.',
            ],
        ],
    ];

    /**
     * Start server transfer.
     */
    #[Endpoint('Transfer server', 'Starts a server transfer to another node using the specified target allocations.')]
    #[BodyParam('node_id', 'integer', 'The destination node ID.', required: true, example: 2)]
    #[BodyParam('allocation_id', 'integer', 'The primary allocation ID on the destination node.', required: true, example: 10)]
    #[BodyParam('allocation_additional', 'integer[]', 'Additional allocation IDs on the destination node.', required: false, example: [11, 12])]
    #[ScribeResponse(status: 204, description: 'Server transfer started.')]
    #[ScribeResponse(self::TRANSFER_NOT_VIABLE_ERROR, status: 400, description: 'The destination node cannot fit the server or the server cannot currently be transferred.')]
    public function __invoke(TransferServerRequest $request, InitiatesTransfers $transfers, Server $server): Response
    {
        $data = $request->payload();
        $node = Node::query()->findOrFail($data['node_id']);

        $transfers->initiate($server, $node, $data['allocation_id'], $data['allocation_additional']);

        Activity::event('admin:server.transfer')
            ->subject($server)
            ->property('name', $server->name)
            ->property('node_id', $node->id)
            ->log();

        return $this->returnNoContent();
    }
}
