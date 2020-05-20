<?php

namespace Pterodactyl\Http\Controllers\Admin\Mounts;

use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Http\Controllers\Controller;

class MountController extends Controller
{
    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * LocationController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $repository
     */
    public function __construct(
        LocationRepositoryInterface $repository
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
            'locations' => $this->repository->getAllWithDetails(),
        ]);
    }
}
