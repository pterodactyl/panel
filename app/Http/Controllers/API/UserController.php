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

use Pterodactyl\Models;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Exceptions\DisplayValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @Resource("Users")
 */
class UserController extends BaseController
{
    public function __construct()
    {
    }

    /**
     * List All Users.
     *
     * Lists all users currently on the system.
     *
     * @Get("/users/{?page}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("page", type="integer", description="The page of results to view.", default=1)
     * })
     * @Response(200)
     */
    public function lists(Request $request)
    {
        return Models\User::all()->toArray();
    }

    /**
     * List Specific User.
     *
     * Lists specific fields about a user or all fields pertaining to that user.
     *
     * @Get("/users/{id}/{fields}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the user to get information on."),
     *      @Parameter("fields", type="string", required=false, description="A comma delimidated list of fields to include.")
     * })
     * @Response(200)
     */
    public function view(Request $request, $id)
    {
        $query = Models\User::where((is_numeric($id) ? 'id' : 'email'), $id);

        if (! is_null($request->input('fields'))) {
            foreach (explode(',', $request->input('fields')) as $field) {
                if (! empty($field)) {
                    $query->addSelect($field);
                }
            }
        }

        try {
            if (! $query->first()) {
                throw new NotFoundHttpException('No user by that ID was found.');
            }

            $user = $query->first();
            $userArray = $user->toArray();
            $userArray['servers'] = Models\Server::select('id', 'uuid', 'node', 'suspended')->where('owner', $user->id)->get();

            return $userArray;
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException('There was an issue with the fields passed in the request.');
        }
    }

    /**
     * Create a New User.
     *
     * @Post("/users")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *          "email": "foo@example.com",
     *          "password": "foopassword",
     *          "admin": false,
     *          "custom_id": 123
     *       }, headers={"Authorization": "Bearer <token>"}),
     *       @Response(201),
     *       @Response(422)
     * })
     */
    public function create(Request $request)
    {
        try {
            $user = new UserRepository;
            $create = $user->create($request->only([
                'email', 'username', 'name_first', 'name_last', 'password', 'root_admin', 'custom_id',
            ]));
            $create = $user->create($request->input('email'), $request->input('password'), $request->input('admin'), $request->input('custom_id'));

            return ['id' => $create];
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
     * The data sent in the request will be used to update the existing user on the system.
     *
     * @Patch("/users/{id}")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *          "email": "new@email.com"
     *      }, headers={"Authorization": "Bearer <token>"}),
     *      @Response(200, body={"email": "new@email.com"}),
     *      @Response(422)
     * })
     * @Parameters({
     *         @Parameter("id", type="integer", required=true, description="The ID of the user to modify.")
     * })
     */
    public function update(Request $request, $id)
    {
        try {
            $user = new UserRepository;
            $user->update($id, $request->only([
                'username', 'email', 'name_first', 'name_last', 'password', 'root_admin', 'language',
            ]));

            return Models\User::findOrFail($id);
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
     * @Delete("/users/{id}")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request(headers={"Authorization": "Bearer <token>"}),
     *      @Response(204),
     *      @Response(422)
     * })
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the user to delete.")
     * })
     */
    public function delete(Request $request, $id)
    {
        try {
            $user = new UserRepository;
            $user->delete($id);

            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to delete this user due to an error.');
        }
    }
}
