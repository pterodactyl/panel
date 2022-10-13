<?php

namespace Pterodactyl\Repositories;

use InvalidArgumentException;
use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Contracts\Repository\RepositoryInterface;

abstract class Repository implements RepositoryInterface
{
    protected array $columns = ['*'];

    protected Model $model;

    protected bool $withFresh = true;

    /**
     * Repository constructor.
     */
    public function __construct(protected Application $app)
    {
        $this->initializeModel($this->model());
    }

    /**
     * Return the model backing this repository.
     */
    abstract public function model(): string;

    /**
     * Return the model being used for this repository.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Setup column selection functionality.
     *
     * @param array|string $columns
     */
    public function setColumns($columns = ['*']): self
    {
        $clone = clone $this;
        $clone->columns = is_array($columns) ? $columns : func_get_args();

        return $clone;
    }

    /**
     * Return the columns to be selected in the repository call.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Stop repository update functions from returning a fresh
     * model when changes are committed.
     */
    public function withoutFreshModel(): self
    {
        return $this->setFreshModel(false);
    }

    /**
     * Return a fresh model with a repository updates a model.
     */
    public function withFreshModel(): self
    {
        return $this->setFreshModel();
    }

    /**
     * Set whether the repository should return a fresh model
     * when changes are committed.
     */
    public function setFreshModel(bool $fresh = true): self
    {
        $clone = clone $this;
        $clone->withFresh = $fresh;

        return $clone;
    }

    /**
     * Take the provided model and make it accessible to the rest of the repository.
     */
    protected function initializeModel(string ...$model): mixed
    {
        switch (count($model)) {
            case 1:
                return $this->model = $this->app->make($model[0]);
            case 2:
                return $this->model = call_user_func([$this->app->make($model[0]), $model[1]]);
            default:
                throw new InvalidArgumentException('Model must be a FQDN or an array with a count of two.');
        }
    }
}
