<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Console\Commands\User;

use Webmozart\Assert\Assert;
use Illuminate\Console\Command;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class DeleteUserCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService
     */
    protected $deletionService;

    /**
     * @var string
     */
    protected $description = 'Deletes a user from the Panel if no servers are attached to their account.';

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var string
     */
    protected $signature = 'p:user:delete {--user=}';

    public function __construct(
        UserDeletionService $deletionService,
        UserRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->deletionService = $deletionService;
        $this->repository = $repository;
    }

    /**
     * @return bool
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function handle()
    {
        $search = $this->option('user') ?? $this->ask(trans('command/messages.user.search_users'));
        Assert::notEmpty($search, 'Search term must be a non-null value, received %s.');

        $results = $this->repository->search($search)->all();
        if (count($results) < 1) {
            $this->error(trans('command/messages.user.no_users_found'));
            if (! $this->option('no-interaction')) {
                return $this->handle();
            }

            return false;
        }

        if (! $this->option('no-interaction')) {
            $tableValues = [];
            foreach ($results as $user) {
                $tableValues[] = [$user->id, $user->email, $user->name];
            }

            $this->table(['User ID', 'Email', 'Name'], $tableValues);
            if (! $deleteUser = $this->ask(trans('command/messages.user.select_search_user'))) {
                return $this->handle();
            }
        } else {
            if (count($results) > 1) {
                $this->error(trans('command/messages.user.multiple_found'));

                return false;
            }

            $deleteUser = $results->first();
        }

        if ($this->confirm(trans('command/messages.user.confirm_delete')) || $this->option('no-interaction')) {
            $this->deletionService->handle($deleteUser);
            $this->info(trans('command/messages.user.deleted'));
        }
    }
}
