<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Throwable;

final readonly class UpdateUser implements UpdatesUsers
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Update the user model instance and return the updated model. The hashed
     * password cast on the model takes care of hashing a new password.
     *
     * @param  UserUpdateData  $data
     *
     * @throws Throwable
     */
    public function update(User $user, array $data): User
    {
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);
        unset($data['extensions']);

        if (empty(Arr::get($data, 'password'))) {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data, $extensions): void {
            $user->forceFill($data)->saveOrFail();
            $this->extensions->save($user, $extensions);
        });

        if (isset($data['password'])) {
            event(new PasswordChanged($user));
        }

        return $user;
    }
}
