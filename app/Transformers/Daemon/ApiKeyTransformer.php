<?php

namespace App\Transformers\Daemon;

use Carbon\Carbon;
use App\Models\DaemonKey;
use App\Models\Permission;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;
use App\Contracts\Repository\SubuserRepositoryInterface;
use App\Contracts\Repository\DaemonKeyRepositoryInterface;

class ApiKeyTransformer extends TransformerAbstract
{
    /**
     * @var \App\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    private $keyRepository;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * ApiKeyTransformer constructor.
     *
     * @param \App\Contracts\Repository\DaemonKeyRepositoryInterface $keyRepository
     * @param \App\Contracts\Repository\SubuserRepositoryInterface   $repository
     */
    public function __construct(DaemonKeyRepositoryInterface $keyRepository, SubuserRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->keyRepository = $keyRepository;
    }

    /**
     * Return a listing of servers that a daemon key can access.
     *
     * @param \App\Models\DaemonKey $key
     * @return array
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
            if (! is_null(Arr::get($mappings, $permission))) {
                $daemonPermissions[] = Arr::get($mappings, $permission);
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
