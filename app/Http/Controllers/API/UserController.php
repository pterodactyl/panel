<?php
/**
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

namespace Pterodactyl\Http\Controllers\API;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Dingo\Api\Exception\ResourceException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Exceptions\DisplayValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UserController extends BaseController
{
    /**
     * Lists all users currently on the system.
     *
     * @param  Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        return User::all()->toArray();
    }

    /**
     * Lists specific fields about a user or all fields pertaining to that user.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return array
     */
    public function view(Request $request, $id)
    {
        $user = User::with('servers')->where((is_numeric($id) ? 'id' : 'email'), $id)->firstOrFail();

        $user->servers->transform(function ($item) {
            return collect($item)->only([
                'id', 'node_id', 'uuidShort',
                'uuid', 'name', 'suspended',
                'owner_id',
            ]);
        });

        if (! is_null($request->input('fields'))) {
            $fields = explode(',', $request->input('fields'));
            if (! empty($fields) && is_array($fields)) {
                return collect($user)->only($fields);
            }
        }

        return $user->toArray();
    }

    /**
     * Create a New User.
     *
     * @param  Request  $request
     * @return array
     */
    public function create(Request $request)
    {
        $repo = new UserRepository;

        try {
            $user = $user->create($request->only([
                'email', 'password', 'name_first',
                'name_last', 'username', 'root_admin',
            ]));

            return ['id' => $user->id];
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to create a user on the system due to an error.');
        }
    }

    /**
     * Update an Existing User.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return array
     */
    public function update(Request $request, $id)
    {
        $repo = new UserRepository;

        try {
            $user = $repo->update($id, $request->only([
                'email', 'password', 'name_first',
                'name_last', 'username', 'root_admin',
            ]));

            return ['id' => $id];
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to update a user on the system due to an error.');
        }
    }

    /**
     * Delete a User.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return void
     */
    public function delete(Request $request, $id)
    {
        $repo = new UserRepository;

        try {
            $repo->delete($id);

            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to delete this user due to an error.');
        }
    }
}
