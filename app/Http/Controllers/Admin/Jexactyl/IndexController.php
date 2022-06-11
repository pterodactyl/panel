<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class IndexController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    private $versionService;

    /**
     * IndexController constructor.
     */
    public function __construct(SoftwareVersionService $versionService)
    {
        $this->versionService = $versionService;
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        return view('admin.jexactyl.index', [
            'version' => $this->versionService,
        ]);
    }
}
