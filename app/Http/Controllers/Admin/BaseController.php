<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class BaseController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    private $version;

    /**
     * BaseController constructor.
     *
     * @param \Pterodactyl\Services\Helpers\SoftwareVersionService $version
     */
    public function __construct(SoftwareVersionService $version)
    {
        $this->version = $version;
    }

    /**
     * Return the admin index view.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // return view('admin.index', ['version' => $this->version]);
        return view('templates/base.core');
    }
}
