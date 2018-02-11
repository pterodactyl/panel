<?php

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Hashing\HashManager;
use Pterodactyl\Traits\Services\HasUserLevels;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\RevokeMultipleDaemonKeysService;

class UserUpdateService
{
    use HasUserLevels;

    /**
     * @var \Illuminate\Hashing\HashManager
     */
    private $hasher;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\RevokeMultipleDaemonKeysService
     */
    private $revocationService;

    /**
     * UpdateService constructor.
     *
     * @param \Illuminate\Hashing\HashManager                                  $hasher
     * @param \Pterodactyl\Services\DaemonKeys\RevokeMultipleDaemonKeysService $revocationService
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface        $repository
     */
    public function __construct(
        HashManager $hasher,
        RevokeMultipleDaemonKeysService $revocationService,
        UserRepositoryInterface $repository
    ) {
        $this->hasher = $hasher;
        $this->repository = $repository;
        $this->revocationService = $revocationService;
    }

    /**
     * Update the user model instance. If the user has been removed as an administrator
     * revoke all of the authentication tokens that have beenn assigned to their account.
     *
     * @param \Pterodactyl\Models\User $user
     * @param array                    $data
     * @return \Illuminate\Support\Collection
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(User $user, array $data): Collection
    {
        if (! empty(array_get($data, 'password'))) {
            $data['password'] = $this->hasher->make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($this->isUserLevel(User::USER_LEVEL_ADMIN)) {
            if (array_get($data, 'root_admin', 0) == 0 && $user->root_admin) {
                $this->revocationService->handle($user, array_get($data, 'ignore_connection_error', false));
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
