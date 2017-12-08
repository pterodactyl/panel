<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Pterodactyl\Http\Controllers\Controller;

class IndexController extends Controller
{
    public function index()
    {
        return view('admin.settings');
    }
}
