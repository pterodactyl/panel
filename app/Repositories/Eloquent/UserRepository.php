<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Pterodactyl\Repositories\Concerns\Searchable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    use Searchable;

    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Return all users with counts of servers and subusers of servers.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllUsersWithCounts(): LengthAwarePaginator
    {
        return $this->getBuilder()->withCount('servers', 'subuserOf')
            ->search($this->getSearchTerm())
            ->paginate(50, $this->getColumns());
    }

    /**
     * Return all matching models for a user in a format that can be used for dropdowns.
     *
     * @param string|null $query
     * @return \Illuminate\Support\Collection
     */
    public function filterUsersByQuery(?string $query): Collection
    {
        $this->setColumns([
            'id', 'email', 'username', 'name_first', 'name_last',
        ]);

        $instance = $this->getBuilder()->search($query)->get($this->getColumns());

        return $instance->transform(function ($item) {
            $item->md5 = md5(strtolower($item->email));

            return $item;
        });
    }
}
