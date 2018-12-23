<?php

namespace Pterodactyl\Services\DaemonKeys;

use Pterodactyl\Models\User;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepository;

class RevokeMultipleDaemonKeysService
{
    /**
     * @var array
     */
    protected $exceptions = [];

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    private $repository;

    /**
     * RevokeMultipleDaemonKeysService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface     $repository
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     */
    public function __construct(
        DaemonKeyRepositoryInterface $repository,
        DaemonServerRepository $daemonRepository
    ) {
        $this->daemonRepository = $daemonRepository;
        $this->repository = $repository;
    }

    /**
     * Grab all of the keys that exist for a single user and delete them from all
     * daemon's that they are assigned to. If connection fails, this function will
     * return an error.
     *
     * @param \Pterodactyl\Models\User $user
     * @param bool                     $ignoreConnectionErrors
     */
    public function handle(User $user, bool $ignoreConnectionErrors = false)
    {
        $keys = $this->repository->getKeysForRevocation($user);

        $keys->groupBy('node.id')->each(function ($group, $nodeId) use ($ignoreConnectionErrors) {
            try {
                $this->daemonRepository->setNode(collect($group)->first()->getRelation('node'))->revokeAccessKey(collect($group)->pluck('secret')->toArray());
            } catch (RequestException $exception) {
                if (! $ignoreConnectionErrors) {
                    throw new DaemonConnectionException($exception);
                }

                $this->setConnectionException($nodeId, $exception);
            }

            $this->repository->deleteKeys(collect($group)->pluck('id')->toArray());
        });
    }

    /**
     * Returns an array of exceptions that were returned by the handle function.
     *
     * @return RequestException[]
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Add an exception for a node to the array.
     *
     * @param int                                    $node
     * @param \GuzzleHttp\Exception\RequestException $exception
     */
    protected function setConnectionException(int $node, RequestException $exception)
    {
        $this->exceptions[$node] = $exception;
    }
}
