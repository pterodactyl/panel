<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Services\Helpers\SoftwareVersionService;

class BaseController extends Controller
{
    /**
     * @var \App\Services\Helpers\SoftwareVersionService
     */
    private $version;

    /**
     * BaseController constructor.
     *
     * @param \App\Services\Helpers\SoftwareVersionService $version
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
        return view('admin.index', ['version' => $this->version]);
    }
}
