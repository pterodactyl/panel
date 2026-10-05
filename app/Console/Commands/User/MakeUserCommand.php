<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\User;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Users\CreatesUsers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\UserRules;
use Throwable;

#[Description('Creates a user on the system via the CLI.')]
#[Signature('p:user:make {--email=} {--username=} {--name-first=} {--name-last=} {--password=} {--admin=} {--no-password}')]
class MakeUserCommand extends Command
{
    /**
     * Handle command request to create a new user.
     *
     * @throws Throwable
     */
    public function handle(CreatesUsers $users): int
    {
        $root_admin = $this->option('admin') ?? $this->confirm(trans('command/messages.user.ask_admin'));
        $email = $this->option('email') ?? $this->ask(trans('command/messages.user.ask_email'));
        $username = $this->option('username') ?? $this->ask(trans('command/messages.user.ask_username'));
        $name_first = $this->option('name-first') ?? $this->ask(trans('command/messages.user.ask_name_first'));
        $name_last = $this->option('name-last') ?? $this->ask(trans('command/messages.user.ask_name_last'));

        if (($password = $this->option('password')) === null && ! $this->option('no-password')) {
            $this->warn(trans('command/messages.user.ask_password_help'));
            $this->line(trans('command/messages.user.ask_password_tip'));
            $password = $this->secret(trans('command/messages.user.ask_password'));
        }

        $data = ['email' => $email, 'username' => $username, 'name_first' => $name_first, 'name_last' => $name_last, 'password' => $password, 'root_admin' => $root_admin];

        $validator = Validator::make($data, UserRules::rules());
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $user = $users->create([
            'email' => JsonValueGuard::string($email),
            'username' => JsonValueGuard::string($username),
            'name_first' => JsonValueGuard::string($name_first),
            'name_last' => JsonValueGuard::string($name_last),
            'password' => JsonValueGuard::nullableString($password),
            'root_admin' => filter_var($root_admin, FILTER_VALIDATE_BOOL),
        ]);

        Activity::event('user:user.create')
            ->subject($user)
            ->property(['email' => $user->email, 'username' => $user->username, 'admin' => $user->root_admin])
            ->log();

        $this->table(['Field', 'Value'], [
            ['UUID', $user->uuid],
            ['Email', $user->email],
            ['Username', $user->username],
            ['Name', $user->name],
            ['Admin', $user->root_admin ? 'Yes' : 'No'],
        ]);

        return 0;
    }
}
