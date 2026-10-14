<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\User;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Pterodactyl\Contracts\Users\DeletesUsers;
use Pterodactyl\Models\User;

#[Description('Deletes a user from the Panel if no servers are attached to their account.')]
#[Signature('p:user:delete {--user=}')]
class DeleteUserCommand extends Command
{
    public function handle(DeletesUsers $users): int
    {
        $search = $this->option('user') ?? $this->ask(trans('command/messages.user.search_users'));
        if (! is_string($search) || empty($search)) {
            throw new InvalidArgumentException(sprintf('Search term should be an email address, got: %s.', get_debug_type($search)));
        }

        $results = User::query()
            ->where('id', 'LIKE', "$search%")
            ->orWhere('username', 'LIKE', "$search%")
            ->orWhere('email', 'LIKE', "$search%")
            ->get();

        if (count($results) < 1) {
            $this->error(trans('command/messages.user.no_users_found'));
            if ($this->input->isInteractive()) {
                return $this->handle($users);
            }

            return 1;
        }

        if ($this->input->isInteractive()) {
            $tableValues = [];
            foreach ($results as $user) {
                $tableValues[] = [$user->id, $user->email, $user->name];
            }

            $this->table(['User ID', 'Email', 'Name'], $tableValues);
            if (! $deleteUser = $this->ask(trans('command/messages.user.select_search_user'))) {
                return $this->handle($users);
            }
        } else {
            if (count($results) > 1) {
                $this->error(trans('command/messages.user.multiple_found'));

                return 1;
            }

            $deleteUser = $results->first();
        }

        if ($this->confirm(trans('command/messages.user.confirm_delete')) || ! $this->input->isInteractive()) {
            if ($deleteUser instanceof User) {
                $user = $deleteUser;
            } else {
                $id = filter_var($deleteUser, FILTER_VALIDATE_INT);
                throw_if($id === false, InvalidArgumentException::class, 'Expected a numeric user id.');
                $user = User::query()->findOrFail($id);
            }

            $users->delete($user);
            $this->info(trans('command/messages.user.deleted'));
        }

        return 0;
    }
}
