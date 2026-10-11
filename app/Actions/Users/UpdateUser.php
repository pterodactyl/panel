<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

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
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function update(User $user, array $input): User
    {
        $attributes = CreateUser::attributes($input, $user);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        DB::transaction(function () use ($user, $attributes, $extensions): void {
            $user->forceFill($attributes)->saveOrFail();
            $this->extensions->save($user, $extensions);
        });

        if (isset($attributes['password'])) {
            event(new PasswordChanged($user));
        }

        return $user;
    }
}
