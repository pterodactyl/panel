<?php

namespace Pterodactyl\Transformers\Daemon;

use Carbon\Carbon;
use Pterodactyl\Models\DaemonKey;
use Pterodactyl\Models\Permission;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class ApiKeyTransformer extends TransformerAbstract
{
    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    private $keyRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * ApiKeyTransformer constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface $keyRepository
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface   $repository
     */
    public function __construct(DaemonKeyRepositoryInterface $keyRepository, SubuserRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->keyRepository = $keyRepository;
    }

    /**
     * Return a listing of servers that a daemon key can access.
     *
     * @param \Pterodactyl\Models\DaemonKey $key
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function transform(DaemonKey $key)
    {
        $this->keyRepository->loadServerAndUserRelations($key);

        if ($key->user_id === $key->getRelation('server')->owner_id || $key->getRelation('user')->root_admin) {
            return [
                'id' => $key->getRelation('server')->uuid,
                'is_temporary' => true,
                'expires_in' => max(Carbon::now()->diffInSeconds($key->expires_at, false), 0),
                'permissions' => ['s:*'],
            ];
        }

        $subuser = $this->repository->getWithPermissionsUsingUserAndServer($key->user_id, $key->server_id);

        $permissions = $subuser->getRelation('permissions')->pluck('permission')->toArray();
        $mappings = Permission::getPermissions(true);
        $daemonPermissions = ['s:console'];

        foreach ($permissions as $permission) {
            if (! is_null(array_get($mappings, $permission))) {
                $daemonPermissions[] = array_get($mappings, $permission);
            }
        }

        return [
            'id' => $key->getRelation('server')->uuid,
            'is_temporary' => true,
            'expires_in' => max(Carbon::now()->diffInSeconds($key->expires_at, false), 0),
            'permissions' => $daemonPermissions,
        ];
    }
}
