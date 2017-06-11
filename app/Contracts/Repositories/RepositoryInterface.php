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

namespace Pterodactyl\Contracts\Repositories;

use Illuminate\Container\Container;

interface RepositoryInterface
{
    /**
     * RepositoryInterface constructor.
     *
     * @param \Illuminate\Container\Container $container
     */
    public function __construct(Container $container);

    /**
     * Define the model class to be loaded.
     *
     * @return string
     */
    public function model();

    /**
     * Returns the raw model class.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel();

    /**
     * Make the model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Pterodactyl\Exceptions\Repository\RepositoryException
     */
    public function makeModel();

    /**
     * Return all of the currently defined rules.
     *
     * @return array
     */
    public function getRules();

    /**
     * Return the rules to apply when updating a model.
     *
     * @return array
     */
    public function getUpdateRules();

    /**
     * Return the rules to apply when creating a model.
     *
     * @return array
     */
    public function getCreateRules();

    /**
     * Add relations to a model for retrieval.
     *
     * @param  array  $params
     * @return $this
     */
    public function with(...$params);

    /**
     * Add count of related items to model when retrieving.
     *
     * @param  array  $params
     * @return $this
     */
    public function withCount(...$params);

    /**
     * Get all records from the database.
     *
     * @param  array $columns
     * @return mixed
     */
    public function all(array $columns = ['*']);

    /**
     * Return a paginated result set.
     *
     * @param  int   $limit
     * @param  array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($limit = 15, array $columns = ['*']);

    /**
     * Create a new record on the model.
     *
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data);

    /**
     * Update the model.
     *
     * @param         $attributes
     * @param  array  $data
     * @return int
     */
    public function update($attributes, array $data);

    /**
     * Delete a model from the database. Handles soft deletion.
     *
     * @param  int    $id
     * @return mixed
     */
    public function delete($id);

    /**
     * Destroy the model from the database. Ignores soft deletion.
     *
     * @param  int  $id
     * @return mixed
     */
    public function destroy($id);

    /**
     * Find a given model by ID or IDs.
     *
     * @param  int|array  $id
     * @param  array      $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     */
    public function find($id, array $columns = ['*']);

    /**
     * Finds the first record matching a passed array of values.
     *
     * @param  array  $attributes
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findBy(array $attributes, array $columns = ['*']);
}
