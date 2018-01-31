<?php

namespace Pterodactyl\Services\Sftp;

use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthenticateUsingPasswordService
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $userRepository;

    /**
     * AuthenticateUsingPasswordService constructor.
     *
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService   $keyProviderService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface   $userRepository
     */
    public function __construct(
        DaemonKeyProviderService $keyProviderService,
        ServerRepositoryInterface $repository,
        UserRepositoryInterface $userRepository
    ) {
        $this->keyProviderService = $keyProviderService;
        $this->repository = $repository;
        $this->userRepository = $userRepository;
    }

    /**
     * Attempt to authenticate a provded username and password and determine if they
     * have permission to access a given server. This function does not account for
     * subusers currently. Only administrators and server owners can login to access
     * their files at this time.
     *
     * Server must exist on the node that the API call is being made from in order for a
     * valid response to be provided.
     *
     * @param string      $username
     * @param string      $password
     * @param int         $node
     * @param string|null $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function handle(string $username, string $password, int $node, string $server = null): array
    {
        if (is_null($server)) {
            throw new RecordNotFoundException;
        }

        $user = $this->userRepository->setColumns(['id', 'root_admin', 'password'])->findFirstWhere([['username', '=', $username]]);
        if (! password_verify($password, $user->password)) {
            throw new RecordNotFoundException;
        }

        $server = $this->repository->setColumns(['id', 'node_id', 'owner_id', 'uuid', 'installed', 'suspended'])->getByUuid($server);
        if ($server->node_id !== $node || (! $user->root_admin && $server->owner_id !== $user->id)) {
            throw new RecordNotFoundException;
        }

        if ($server->installed !== 1 || $server->suspended) {
            throw new BadRequestHttpException;
        }

        return [
            'server' => $server->uuid,
            'token' => $this->keyProviderService->handle($server, $user),
        ];
    }
}
