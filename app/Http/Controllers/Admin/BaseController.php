<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;

class BaseController extends Controller
{
    /**
     * Return the admin index view.
     */
    public function index(): View
    {
        return view('templates/base.core');
    }
}
