<?php

namespace Pterodactyl\Http\Controllers\Admin\Mounts;

use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\MountRepository;

class MountController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $repository;

    /**
     * MountController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\MountRepository $repository
     */
    public function __construct(
        MountRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Return the mount overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.mounts.index', [
            'mounts' => $this->repository->getAllWithDetails(),
        ]);
    }
}
