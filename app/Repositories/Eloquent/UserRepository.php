<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
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

        $instance = $this->getBuilder()->get($this->getColumns());

        return $instance->transform(function ($item) {
            $item->md5 = md5(strtolower($item->email));

            return $item;
        });
    }

    /**
     * Returns a user with the given id in a format that can be used for dropdowns.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Model
     */
    public function filterById(int $id): \Pterodactyl\Models\Model
    {
        $this->setColumns([
            'id', 'email', 'username', 'name_first', 'name_last',
        ]);

        $model = $this->getBuilder()->findOrFail($id, $this->getColumns())->getModel();
        $model->md5 = md5(strtolower($model->email));

        return $model;
    }
}
