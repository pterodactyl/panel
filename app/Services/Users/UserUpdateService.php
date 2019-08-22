<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Traits\Services\HasUserLevels;
use Illuminate\Contracts\Hashing\Hasher;
use App\Contracts\Repository\UserRepositoryInterface;
use App\Services\DaemonKeys\RevokeMultipleDaemonKeysService;

class UserUpdateService
{
    use HasUserLevels;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Services\DaemonKeys\RevokeMultipleDaemonKeysService
     */
    private $revocationService;

    /**
     * UpdateService constructor.
     *
     * @param \Illuminate\Contracts\Hashing\Hasher                             $hasher
     * @param \App\Services\DaemonKeys\RevokeMultipleDaemonKeysService $revocationService
     * @param \App\Contracts\Repository\UserRepositoryInterface        $repository
     */
    public function __construct(
        Hasher $hasher,
        RevokeMultipleDaemonKeysService $revocationService,
        UserRepositoryInterface $repository
    ) {
        $this->hasher = $hasher;
        $this->repository = $repository;
        $this->revocationService = $revocationService;
    }

    /**
     * Update the user model instance. If the user has been removed as an administrator
     * revoke all of the authentication tokens that have been assigned to their account.
     *
     * @param \App\Models\User $user
     * @param array                    $data
     * @return \Illuminate\Support\Collection
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(User $user, array $data): Collection
    {
        if (! empty(Arr::get($data, 'password'))) {
            $data['password'] = $this->hasher->make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($this->isUserLevel(User::USER_LEVEL_ADMIN)) {
            if (Arr::get($data, 'root_admin', 0) == 0 && $user->root_admin) {
                $this->revocationService->handle($user, Arr::get($data, 'ignore_connection_error', false));
            }
        } else {
            unset($data['root_admin']);
        }

        return collect([
            'model' => $this->repository->update($user->id, $data),
            'exceptions' => $this->revocationService->getExceptions(),
        ]);
    }
}
