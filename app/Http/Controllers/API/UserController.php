<?php

namespace Pterodactyl\Http\Controllers\API;

use Illuminate\Http\Request;

use Pterodactyl\Transformers\UserTransformer;
use Pterodactyl\Models;

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
     * 		@Parameter("page", type="integer", description="The page of results to view.", default=1)
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
     * 		@Parameter("id", type="integer", required=true, description="The ID of the user to get information on."),
     * 		@Parameter("fields", type="string", required=false, description="A comma delimidated list of fields to include.")
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

}
