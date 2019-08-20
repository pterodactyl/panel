<?php

namespace App\Services\Sftp;

use App\Contracts\Repository\UserRepositoryInterface;
use App\Services\DaemonKeys\DaemonKeyProviderService;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Contracts\Repository\SubuserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthenticateUsingPasswordService
{
    /**
     * @var \App\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface
     */
    private $subuserRepository;

    /**
     * AuthenticateUsingPasswordService constructor.
     *
     * @param \App\Services\DaemonKeys\DaemonKeyProviderService    $keyProviderService
     * @param \App\Contracts\Repository\ServerRepositoryInterface  $repository
     * @param \App\Contracts\Repository\SubuserRepositoryInterface $subuserRepository
     * @param \App\Contracts\Repository\UserRepositoryInterface    $userRepository
     */
    public function __construct(
        DaemonKeyProviderService $keyProviderService,
        ServerRepositoryInterface $repository,
        SubuserRepositoryInterface $subuserRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->keyProviderService = $keyProviderService;
        $this->repository = $repository;
        $this->subuserRepository = $subuserRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Attempt to authenticate a provided username and password and determine if they
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
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
        if ($server->node_id !== $node) {
            throw new RecordNotFoundException;
        }

        if (! $user->root_admin && $server->owner_id !== $user->id) {
            $subuser = $this->subuserRepository->getWithPermissionsUsingUserAndServer($user->id, $server->id);
            $permissions = $subuser->getRelation('permissions')->pluck('permission')->toArray();

            if (! in_array('access-sftp', $permissions)) {
                throw new RecordNotFoundException;
            }
        }

        if ($server->installed !== 1 || $server->suspended) {
            throw new BadRequestHttpException;
        }

        return [
            'server' => $server->uuid,
            'token' => $this->keyProviderService->handle($server, $user),
            'permissions' => $permissions ?? ['*'],
        ];
    }
}
