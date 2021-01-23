<?php

namespace Pterodactyl\Repositories;

use InvalidArgumentException;
use Illuminate\Foundation\Application;
use Pterodactyl\Contracts\Repository\RepositoryInterface;

abstract class Repository implements RepositoryInterface
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $columns = ['*'];

    /**
     * @var mixed
     */
    protected $model;

    /**
     * @var bool
     */
    protected $withFresh = true;

    /**
     * Repository constructor.
     */
    public function __construct(Application $application)
    {
        $this->app = $application;

        $this->initializeModel($this->model());
    }

    /**
     * Return the model backing this repository.
     *
     * @return string|\Closure|object
     */
    abstract public function model();

    /**
     * Return the model being used for this repository.
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Setup column selection functionality.
     *
     * @param array|string $columns
     *
     * @return $this
     */
    public function setColumns($columns = ['*'])
    {
        $clone = clone $this;
        $clone->columns = is_array($columns) ? $columns : func_get_args();

        return $clone;
    }

    /**
     * Return the columns to be selected in the repository call.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Stop repository update functions from returning a fresh
     * model when changes are committed.
     *
     * @return $this
     */
    public function withoutFreshModel()
    {
        return $this->setFreshModel(false);
    }

    /**
     * Return a fresh model with a repository updates a model.
     *
     * @return $this
     */
    public function withFreshModel()
    {
        return $this->setFreshModel(true);
    }

    /**
     * Set whether or not the repository should return a fresh model
     * when changes are committed.
     *
     * @return $this
     */
    public function setFreshModel(bool $fresh = true)
    {
        $clone = clone $this;
        $clone->withFresh = $fresh;

        return $clone;
    }

    /**
     * Take the provided model and make it accessible to the rest of the repository.
     *
     * @param array $model
     *
     * @return mixed
     */
    protected function initializeModel(...$model)
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
