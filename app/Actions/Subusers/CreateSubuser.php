<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Subusers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Subusers\CreatesSubusers;
use Pterodactyl\Contracts\Users\CreatesUsers;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Throwable;

final readonly class CreateSubuser implements CreatesSubusers
{
    public function __construct(private CreatesUsers $users) {}

    /**
     * Creates a new user on the system and assigns them access to the provided server.
     * If the email address already belongs to a user on the system a new user will not
     * be created. The returned subuser's user reports wasRecentlyCreated when one was.
     *
     * @param  list<string>  $permissions  The permission keys to grant, deduplicated
     *                                     before they are stored.
     *
     * @throws ServerSubuserExistsException
     * @throws UserIsServerOwnerException
     * @throws Throwable
     */
    public function create(Server $server, string $email, array $permissions): Subuser
    {
        return DB::transaction(function () use ($server, $email, $permissions): Subuser {
            $server = $server->newQuery()->whereKey($server->getKey())->lockForUpdate()->firstOrFail();
            $user = User::query()->where('email', $email)->first() ?? $this->createUser($email);

            throw_if($server->owner_id === $user->id, UserIsServerOwnerException::class, trans('exceptions.subusers.user_is_owner'));
            throw_if($server->subusers()->where('user_id', $user->id)->exists(), ServerSubuserExistsException::class, trans('exceptions.subusers.subuser_exists'));

            return Subuser::query()->create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'permissions' => array_values(array_unique($permissions)),
            ])->setRelation('user', $user);
        });
    }

    private function createUser(string $email): User
    {
        $localPart = strtok($email, '@') ?: $email;
        $username = mb_substr(preg_replace('/([^\w\.-]+)/', '', $localPart) ?? '', 0, 64).Str::random(3);

        return $this->users->create([
            'email' => $email,
            'username' => $username,
            'name_first' => 'Server',
            'name_last' => 'Subuser',
            'root_admin' => false,
        ]);
    }
}
