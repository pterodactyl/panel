<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Debugbar;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.index');
    }

}
