<?php

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Http\Request;
use Webmozart\Assert\Assert;
use Illuminate\Support\Collection;
use Pterodactyl\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

abstract class EloquentRepository extends Repository implements RepositoryInterface
{
    /**
     * @var bool
     */
    protected $useRequestFilters = false;

    /**
     * Determines if the repository function should use filters off the request object
     * present when returning results. This allows repository methods to be called in API
     * context's such that we can pass through ?filter[name]=Dane&sort=desc for example.
     *
     * @param bool $usingFilters
     *
     * @return $this
     */
    public function usingRequestFilters($usingFilters = true)
    {
        $this->useRequestFilters = $usingFilters;

        return $this;
    }

    /**
     * Returns the request instance.
     *
     * @return \Illuminate\Http\Request
     */
    protected function request()
    {
        return $this->app->make(Request::class);
    }

    /**
     * Paginate the response data based on the page para.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function paginate(Builder $instance, int $default = 50)
    {
        if (!$this->useRequestFilters) {
            return $instance->paginate($default);
        }

        return $instance->paginate($this->request()->query('per_page', $default));
    }

    /**
     * Return an instance of the eloquent model bound to this
     * repository instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Return an instance of the builder to use for this repository.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBuilder()
    {
        return $this->getModel()->newQuery();
    }

    /**
     * Create a new record in the database and return the associated model.
     *
     * @return \Illuminate\Database\Eloquent\Model|bool
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(array $fields, bool $validate = true, bool $force = false)
    {
        $instance = $this->getBuilder()->newModelInstance();
        ($force) ? $instance->forceFill($fields) : $instance->fill($fields);

        if (!$validate) {
            $saved = $instance->skipValidation()->save();
        } else {
            if (!$saved = $instance->save()) {
                throw new DataValidationException($instance->getValidator(), $instance);
            }
        }

        return ($this->withFresh) ? $instance->fresh() : $saved;
    }

    /**
     * Find a model that has the specific ID passed.
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function find(int $id)
    {
        try {
            return $this->getBuilder()->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }

    /**
     * Find a model matching an array of where clauses.
     */
    public function findWhere(array $fields): Collection
    {
        return $this->getBuilder()->where($fields)->get($this->getColumns());
    }

    /**
     * Find and return the first matching instance for the given fields.
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function findFirstWhere(array $fields)
    {
        try {
            return $this->getBuilder()->where($fields)->firstOrFail($this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }

    /**
     * Return a count of records matching the passed arguments.
     */
    public function findCountWhere(array $fields): int
    {
        return $this->getBuilder()->where($fields)->count($this->getColumns());
    }

    /**
     * Delete a given record from the database.
     */
    public function delete(int $id, bool $destroy = false): int
    {
        return $this->deleteWhere(['id' => $id], $destroy);
    }

    /**
     * Delete records matching the given attributes.
     */
    public function deleteWhere(array $attributes, bool $force = false): int
    {
        $instance = $this->getBuilder()->where($attributes);

        return ($force) ? $instance->forceDelete() : $instance->delete();
    }

    /**
     * Update a given ID with the passed array of fields.
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Model|bool
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update($id, array $fields, bool $validate = true, bool $force = false)
    {
        try {
            $instance = $this->getBuilder()->where('id', $id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }

        ($force) ? $instance->forceFill($fields) : $instance->fill($fields);

        if (!$validate) {
            $saved = $instance->skipValidation()->save();
        } else {
            if (!$saved = $instance->save()) {
                throw new DataValidationException($instance->getValidator(), $instance);
            }
        }

        return ($this->withFresh) ? $instance->fresh() : $saved;
    }

    /**
     * Update a model using the attributes passed.
     *
     * @param array|\Closure $attributes
     *
     * @return int
     */
    public function updateWhere($attributes, array $values)
    {
        return $this->getBuilder()->where($attributes)->update($values);
    }

    /**
     * Perform a mass update where matching records are updated using whereIn.
     * This does not perform any model data validation.
     */
    public function updateWhereIn(string $column, array $values, array $fields): int
    {
        Assert::notEmpty($column, 'First argument passed to updateWhereIn must be a non-empty string.');

        return $this->getBuilder()->whereIn($column, $values)->update($fields);
    }

    /**
     * Update a record if it exists in the database, otherwise create it.
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateOrCreate(array $where, array $fields, bool $validate = true, bool $force = false)
    {
        foreach ($where as $item) {
            Assert::true(is_scalar($item) || is_null($item), 'First argument passed to updateOrCreate should be an array of scalar or null values, received an array value of %s.');
        }

        try {
            $instance = $this->setColumns('id')->findFirstWhere($where);
        } catch (RecordNotFoundException $exception) {
            return $this->create(array_merge($where, $fields), $validate, $force);
        }

        return $this->update($instance->id, $fields, $validate, $force);
    }

    /**
     * Return all records associated with the given model.
     *
     * @deprecated Just use the model
     */
    public function all(): Collection
    {
        return $this->getBuilder()->get($this->getColumns());
    }

    /**
     * Return a paginated result set using a search term if set on the repository.
     */
    public function paginated(int $perPage): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage, $this->getColumns());
    }

    /**
     * Insert a single or multiple records into the database at once skipping
     * validation and mass assignment checking.
     */
    public function insert(array $data): bool
    {
        return $this->getBuilder()->insert($data);
    }

    /**
     * Insert multiple records into the database and ignore duplicates.
     */
    public function insertIgnore(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        foreach ($values as $key => $value) {
            ksort($value);
            $values[$key] = $value;
        }

        $bindings = array_values(array_filter(array_flatten($values, 1), function ($binding) {
            return !$binding instanceof Expression;
        }));

        $grammar = $this->getBuilder()->toBase()->getGrammar();
        $table = $grammar->wrapTable($this->getModel()->getTable());
        $columns = $grammar->columnize(array_keys(reset($values)));

        $parameters = collect($values)->map(function ($record) use ($grammar) {
            return sprintf('(%s)', $grammar->parameterize($record));
        })->implode(', ');

        $statement = "insert ignore into $table ($columns) values $parameters";

        return $this->getBuilder()->getConnection()->statement($statement, $bindings);
    }

    /**
     * Get the amount of entries in the database.
     *
     * @deprecated just use the count method off a model
     */
    public function count(): int
    {
        return $this->getBuilder()->count();
    }
}
