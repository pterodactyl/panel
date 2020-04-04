<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Cake\Chronos\Chronos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Wings\DaemonTransferRepository;

class ServerTransferController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $nodeRepository;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonTransferRepository
     */
    private $daemonTransferRepository;

    /**
     * ServerTransferController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Pterodactyl\Repositories\Eloquent\NodeRepository $nodeRepository
     * @param DaemonTransferRepository $daemonTransferRepository
     */
    public function __construct(
        ServerRepository $repository,
        NodeRepository $nodeRepository,
        DaemonTransferRepository $daemonTransferRepository
    ) {
        $this->repository = $repository;
        $this->nodeRepository = $nodeRepository;
        $this->daemonTransferRepository = $daemonTransferRepository;
    }

    /**
     * The daemon notifies us about the archive status.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function archive(Request $request, string $uuid)
    {
        $server = $this->repository->getByUuid($uuid);

        // Unsuspend the server and don't continue the transfer.
        if (!$request->input('successful')) {
            // $this->suspensionService->toggle($server, 'unsuspend');
            return JsonResponse::create([], Response::HTTP_NO_CONTENT);
        }

        $now = Chronos::now();
        $signer = new Sha256;

        $token = (new Builder)->issuedBy(config('app.url'))
            ->permittedFor($server->node->getConnectionAddress())
            ->identifiedBy(hash('sha256', $server->uuid), true)
            ->issuedAt($now->getTimestamp())
            ->canOnlyBeUsedAfter($now->getTimestamp())
            ->expiresAt($now->addMinutes(15)->getTimestamp())
            ->relatedTo($server->uuid, true)
            ->getToken($signer, new Key($server->node->daemonSecret));

        // On the daemon transfer repository, make sure to set the node after the server
        // because setServer() tells the repository to use the server's node and not the one
        // we want to specify.
        $this->daemonTransferRepository
            ->setServer($server)
            ->setNode($this->nodeRepository->find($server->transfer->new_node))
            ->notify($server, $server->node, $token->__toString());

        return JsonResponse::create([], Response::HTTP_NO_CONTENT);
    }
}
