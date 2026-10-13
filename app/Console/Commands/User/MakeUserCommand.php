<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\User;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Users\CreatesUsers;
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
        $input = [
            'root_admin' => $this->option('admin') ?? $this->confirm(trans('command/messages.user.ask_admin')),
            'email' => $this->option('email') ?? $this->ask(trans('command/messages.user.ask_email')),
            'username' => $this->option('username') ?? $this->ask(trans('command/messages.user.ask_username')),
            'name_first' => $this->option('name-first') ?? $this->ask(trans('command/messages.user.ask_name_first')),
            'name_last' => $this->option('name-last') ?? $this->ask(trans('command/messages.user.ask_name_last')),
            'password' => $this->option('password'),
        ];

        if ($input['password'] === null && ! $this->option('no-password')) {
            $this->warn(trans('command/messages.user.ask_password_help'));
            $this->line(trans('command/messages.user.ask_password_tip'));
            $input['password'] = $this->secret(trans('command/messages.user.ask_password'));
        }

        try {
            $user = $users->create($input);
        } catch (ValidationException $validationException) {
            foreach ($validationException->validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

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
