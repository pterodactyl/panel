<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Pterodactyl\Contracts\Users\CreatesUsers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\User;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Rules\UserEmail;
use Pterodactyl\Rules\Username;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Throwable;

final readonly class CreateUser implements CreatesUsers
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Rules every new password must pass, wherever it is set. The policy itself is
     * Password::defaults(), set in AppServiceProvider.
     *
     * @return list<string|PasswordRule>
     */
    public static function passwordRules(): array
    {
        return ['string', PasswordRule::default()];
    }

    /**
     * A user's fields. Pass the user being updated so its own email, username and external
     * id are not taken for duplicates.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?User $user = null): array
    {
        return [
            'email' => ['required', 'email:strict', 'between:1,191', new UserEmail, Rule::unique('users', 'email')->ignore($user)],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:191', Rule::unique('users', 'external_id')->ignore($user)],
            'username' => ['required', 'between:1,191', Rule::unique('users', 'username')->ignore($user), new Username],
            'name_first' => ['required', 'string', 'between:1,191'],
            'name_last' => ['required', 'string', 'between:1,191'],
            'password' => ['sometimes', 'nullable', ...self::passwordRules()],
            'root_admin' => ['boolean'],
            'language' => ['string', new In(array_keys((new User)->getAvailableLanguages()))],
        ];
    }

    /**
     * Validates user input and returns the model attributes it describes. Given the user
     * being updated, only the fields in the input are validated and returned, so the rest
     * keep their stored values. A blank password is left out.
     *
     * @param  array<string, mixed>  $input
     * @return array{email?: string, username?: string, name_first?: string, name_last?: string, external_id?: string|null, password?: string, root_admin?: bool, language?: string}
     */
    public static function attributes(array $input, ?User $user = null): array
    {
        $rules = self::rules($user);
        if ($user instanceof User) {
            foreach ($rules as $field => $fieldRules) {
                $rules[$field] = ['sometimes', ...$fieldRules];
            }
        }

        $input = Validator::make($input, $rules)->safe();

        $attributes = [];
        if ($input->has('email')) {
            $attributes['email'] = $input->string('email')->toString();
        }

        if ($input->has('username')) {
            $attributes['username'] = $input->string('username')->toString();
        }

        if ($input->has('name_first')) {
            $attributes['name_first'] = $input->string('name_first')->toString();
        }

        if ($input->has('name_last')) {
            $attributes['name_last'] = $input->string('name_last')->toString();
        }

        if ($input->has('external_id')) {
            $attributes['external_id'] = $input->filled('external_id') ? $input->string('external_id')->toString() : null;
        }

        // A blank password, spaces included, is no password at all.
        if ($input->filled('password')) {
            $attributes['password'] = $input->string('password')->toString();
        }

        if ($input->has('root_admin')) {
            $attributes['root_admin'] = $input->boolean('root_admin');
        }

        if ($input->filled('language')) {
            $attributes['language'] = $input->string('language')->toString();
        }

        return $attributes;
    }

    /**
     * Create a new user on the system. When no password is provided a random one is
     * set and a password reset token is generated for the account welcome email.
     *
     * The UUID and password hash are handled by the model itself (HasVersion4Uuids
     * and the hashed password cast).
     *
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function create(array $input): User
    {
        $attributes = self::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        $generateResetToken = ! isset($attributes['password']);
        $attributes['password'] ??= Str::random(30);

        [$user, $token] = DB::transaction(function () use ($attributes, $generateResetToken, $extensions): array {
            $user = User::query()->create($attributes);
            $this->extensions->save($user, $extensions);

            return [$user->refresh(), $generateResetToken ? Password::createToken($user) : null];
        });

        Activity::event('user:user.create')
            ->subject($user)
            ->property(['email' => $user->email, 'username' => $user->username, 'admin' => $user->root_admin])
            ->log();

        $user->notify(new AccountCreated($user, $token));

        return $user;
    }
}
