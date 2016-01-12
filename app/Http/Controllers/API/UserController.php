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
    public function getUsers(Request $request) {
        $users = Models\User::paginate(15);
        return $this->response->paginator($users, new UserTransformer);
    }

}
