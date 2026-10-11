<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\User;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Fluent;
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
        $root_admin = $this->option('admin') === null
            ? $this->confirm(trans('command/messages.user.ask_admin'))
            : Fluent::make($this->options())->string('admin')->toString();
        $input = Fluent::make([
            'email' => $this->option('email') ?? $this->ask(trans('command/messages.user.ask_email')),
            'username' => $this->option('username') ?? $this->ask(trans('command/messages.user.ask_username')),
            'name_first' => $this->option('name-first') ?? $this->ask(trans('command/messages.user.ask_name_first')),
            'name_last' => $this->option('name-last') ?? $this->ask(trans('command/messages.user.ask_name_last')),
            'password' => $this->option('password'),
        ]);

        if ($input->get('password') === null && ! $this->option('no-password')) {
            $this->warn(trans('command/messages.user.ask_password_help'));
            $this->line(trans('command/messages.user.ask_password_tip'));
            $input->set('password', $this->secret(trans('command/messages.user.ask_password')));
        }

        $text = fn (string $key): ?string => $input->get($key) === null ? null : $input->string($key)->toString();

        try {
            $user = $users->create([
                'email' => $text('email'),
                'username' => $text('username'),
                'name_first' => $text('name_first'),
                'name_last' => $text('name_last'),
                'password' => $text('password'),
                'root_admin' => $root_admin,
            ]);
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
