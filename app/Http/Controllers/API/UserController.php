<?php

namespace Pterodactyl\Http\Controllers\API;

use Illuminate\Http\Request;

use Dingo\Api\Exception\StoreResourceFailedException;

use Pterodactyl\Transformers\UserTransformer;
use Pterodactyl\Models;
use Pterodactyl\Repositories\UserRepository;

/**
 * @Resource("Users", uri="/users")
 */
class UserController extends BaseController
{

    /**
     * List All Users
     *
     * Lists all users currently on the system.
     *
     * @Get("/{?page}")
     * @Versions({"v1"})
     * @Parameters({
     *		@Parameter("page", type="integer", description="The page of results to view.", default=1)
     * })
     * @Response(200)
     */
    public function getUsers(Request $request)
    {
        $users = Models\User::paginate(15);
        return $this->response->paginator($users, new UserTransformer);
    }

    /**
     * List Specific User
     *
     * Lists specific fields about a user or all fields pertaining to that user.
     *
     * @Get("/{id}/{fields}")
     * @Versions({"v1"})
     * @Parameters({
     *		@Parameter("id", type="integer", required=true, description="The ID of the user to get information on."),
     *  	@Parameter("fields", type="string", required=false, description="A comma delimidated list of fields to include.")
     * })
     * @Response(200)
     */
    public function getUserByID(Request $request, $id, $fields = null)
    {
        $query = Models\User::where('id', $id);

        if (!is_null($fields)) {
            foreach(explode(',', $fields) as $field) {
                if (!empty($field)) {
                    $query->addSelect($field);
                }
            }
        }

        return $query->first();
    }

    /**
     * Create a New User
     *
     * @Post("/")
     * @Versions({"v1"})
     * @Transaction({
     * 		@Request({
     *   		"email": "foo@example.com",
     *     		"password": "foopassword",
     *       	"admin": false
     *       }, headers={"Authorization": "Bearer <jwt-token>"}),
     *       @Response(200, body={"id": 1}),
     *       @Response(422, body{
     *       	"message": "A validation error occured.",
     *        	"errors": {
     *         		"email": ["The email field is required."],
     *           	"password": ["The password field is required."],
     *            	"admin": ["The admin field is required."]
     *          },
     *          "status_code": 422
     *       })
     * })
     */
    public function postUsers(Request $request)
    {
        try {
            $user = new UserRepository;
            $create = $user->create($request->input('email'), $request->input('password'), $request->input('admin'));
            return [ 'id' => $create ];
        } catch (\Pterodactyl\Exceptions\DisplayValidationException $ex) {
            throw new StoreResourceFailedException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (\Exception $ex) {
            throw new StoreResourceFailedException('Unable to create a user on the system due to an error.');
        }
    }

    /**
     * Update an Existing User
     *
     * The data sent in the request will be used to update the existing user on the system.
     *
     * @Patch("/{id}")
     * @Versions({"v1"})
     * @Transaction({
     * 		@Request({
     *   		"email": "new@email.com"
     *     	}, headers={"Authorization": "Bearer <jwt-token>"}),
     *      @Response(200, body={"email": "new@email.com"}),
     *      @Response(422)
     * })
     * @Parameters({
     *         @Parameter("id", type="integer", required=true, description="The ID of the user to modify.")
     * })
     */
    public function patchUser(Request $request, $id)
    {
        //
    }

    /**
     * Delete a User
     *
     * @Delete("/{id}")
     * @Versions({"v1"})
     * @Transaction({
     * 		@Request(headers={"Authorization": "Bearer <jwt-token>"}),
     *   	@Response(204),
     *    	@Response(422)
     * })
     * @Parameters({
     * 		@Parameter("id", type="integer", required=true, description="The ID of the user to delete.")
     * })
     */
    public function deleteUser(Request $request, $id)
    {
        //
    }

}
