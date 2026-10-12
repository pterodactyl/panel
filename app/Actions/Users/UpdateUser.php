<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateUser implements UpdatesUsers
{
    public function __construct(private ExtensionFields $extensions) {}

    public function update(User $user, array $input): User
    {
        $rules = array_map(fn (array $rules): array => ['sometimes', ...$rules], CreateUser::rules($user));
        $validated = Validator::validate($input, $rules);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // Blank fields change nothing, a blank password included, apart from a blank external id,
        // which clears it.
        $attributes = array_filter($validated, filled(...));
        if (array_key_exists('external_id', $validated)) {
            $attributes['external_id'] ??= null;
        }

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
