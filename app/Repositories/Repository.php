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

namespace Pterodactyl\Repository;

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
     *
     * @param \Illuminate\Foundation\Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;

        $this->setModel($this->model());
    }

    /**
     * Take the provided model and make it accessible to the rest of the repository.
     *
     * @param string|array $model
     * @return mixed
     */
    protected function setModel($model)
    {
        if (is_array($model)) {
            if (count($model) !== 2) {
                throw new \InvalidArgumentException(
                    printf('setModel expected exactly 2 parameters, %d received.', count($model))
                );
            }

            return $this->model = call_user_func(
                $model[1],
                $this->app->make($model[0])
            );
        }

        return $this->model = $this->app->make($model);
    }

    /**
     * @return mixed
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
     * @param array $columns
     * @return $this
     */
    public function withColumns($columns = ['*'])
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
     * Set repository to not return a fresh record from the DB when completed.
     *
     * @return $this
     */
    public function withoutFresh()
    {
        $clone = clone $this;
        $clone->withFresh = false;

        return $clone;
    }
}
