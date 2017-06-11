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

use Pterodactyl\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Pterodactyl\Repositories\Repository;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repositories\UserInterface;

class UserRepository extends Repository implements UserInterface
{
    /**
     * Dependencies to automatically inject into the repository.
     *
     * @var array
     */
    protected $inject = [
        'guard' => Guard::class,
    ];

    /**
     * Return the model to be used for the repository.
     *
     * @return string
     */
    public function model()
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    public function search($term)
    {
        $this->model->search($term);

        return $this;
    }

    public function delete($id)
    {
        $user = $this->model->withCount('servers')->find($id);

        if ($this->guard->user() && $this->guard->user()->id === $user->id) {
            throw new DisplayException('You cannot delete your own account.');
        }

        if ($user->server_count > 0) {
            throw new DisplayException('Cannot delete an account that has active servers attached to it.');
        }

        return $user->delete();
    }
}
