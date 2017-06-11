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

namespace Pterodactyl\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Contracts\Repositories\RepositoryInterface;
use Pterodactyl\Exceptions\Repository\RepositoryException;

abstract class Repository implements RepositoryInterface
{
    const RULE_UPDATED = 'updated';
    const RULE_CREATED = 'created';

    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Array of classes to inject automatically into the container.
     *
     * @var array
     */
    protected $inject = [];

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Array of validation rules that can be accessed from this repository.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        foreach ($this->inject as $key => $value) {
            if (isset($this->{$key})) {
                throw new \Exception('Cannot override a defined object in this class.');
            }

            $this->{$key} = $this->container->make($value);
        }

        $this->makeModel();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function model();

    /**
     * {@inheritdoc}
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * {@inheritdoc}
     */
    public function makeModel()
    {
        $model = $this->container->make($this->model());

        if (! $model instanceof Model) {
            throw new RepositoryException(
                "Class {$this->model()} must be an instance of \\Illuminate\\Database\\Eloquent\\Model"
            );
        }

        return $this->model = $model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateRules()
    {
        if (array_key_exists(self::RULE_UPDATED, $this->rules)) {
            return $this->rules[self::RULE_UPDATED];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateRules()
    {
        if (array_key_exists(self::RULE_CREATED, $this->rules)) {
            return $this->rules[self::RULE_CREATED];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function with(...$params)
    {
        $this->model = $this->model->with($params);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withCount(...$params)
    {
        $this->model = $this->model->withCount($params);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*'])
    {
        return $this->model->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($limit = 15, array $columns = ['*'])
    {
        return $this->model->paginate($limit, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update($attributes, array $data)
    {
        // If only a number is passed, we assume it is an ID
        // for the specific model at hand.
        if (is_numeric($attributes)) {
            $attributes = [['id', '=', $attributes]];
        }

        return $this->model->where($attributes)->get()->each->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->model->find($id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($id)
    {
        return $this->model->find($id)->forceDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*'])
    {
        return $this->model->find($id, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $attributes, array $columns = ['*'])
    {
        return $this->model->where($attributes)->first($columns);
    }
}
