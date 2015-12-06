<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Debugbar;
use Pterodactyl\Models\User;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountsController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {

        // All routes in this controller are protected by the authentication middleware.
        $this->middleware('auth');
        $this->middleware('admin');

    }

    public function getIndex(Request $request)
    {
        return view('admin.accounts.index', [
            'users' => User::paginate(20)
        ]);
    }

    public function getNew(Request $request)
    {
        //
    }

    public function getView(Request $request, $id)
    {
        //
    }

}
