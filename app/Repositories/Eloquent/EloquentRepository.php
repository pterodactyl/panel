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

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Repository\Repository;
use Pterodactyl\Contracts\Repository\RepositoryInterface;

abstract class EloquentRepository extends Repository implements RepositoryInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBuilder()
    {
        return $this->getModel()->newQuery();
    }

    /**
     * Create a new model instance and persist it to the database.
     * @param  array $fields
     * @param  bool  $validate
     * @param  bool  $force
     * @return bool|\Illuminate\Database\Eloquent\Model
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create($fields, $validate = true, $force = false)
    {
        $instance = $this->getBuilder()->newModelInstance();

        if ($force) {
            $instance->forceFill($fields);
        } else {
            $instance->fill($fields);
        }

        if (! $validate) {
            $saved = $instance->skipValidation()->save();
        } else {
            if (! $saved = $instance->save()) {
                throw new DataValidationException($instance->getValidator());
            }
        }

        return ($this->withFresh) ? $instance->fresh() : $saved;
    }

    /**
     * Return a record from the database for a given ID.
     *
     * @param  int $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function find($id)
    {
        $instance = $this->getBuilder()->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }

    public function findWhere($fields)
    {
        // TODO: Implement findWhere() method.
    }

    /**
     * Delete a record from the DB given an ID.
     *
     * @param  int $id
     * @param  bool $destroy
     * @return bool|null
     */
    public function delete($id, $destroy = false)
    {
        if ($destroy) {
            return $this->getBuilder()->where($this->getModel()->getKeyName(), $id)->forceDelete();
        }

        return $this->getBuilder()->where($this->getModel()->getKeyName(), $id)->delete();
    }

    /**
     * @param  int   $id
     * @param  array $fields
     * @param  bool  $validate
     * @param  bool  $force
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update($id, $fields, $validate = true, $force = false)
    {
        $instance = $this->getBuilder()->where('id', $id)->first();

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        if ($force) {
            $instance->forceFill($fields);
        } else {
            $instance->fill($fields);
        }

        if (! $validate) {
            $saved = $instance->skipValidation()->save();
        } else {
            if (! $saved = $instance->save()) {
                throw new DataValidationException($instance->getValidator());
            }
        }

        return ($this->withFresh) ? $instance->fresh($this->getColumns()) : $saved;
    }

    public function massUpdate($fields)
    {
        // TODO: Implement massUpdate() method.
    }
}
