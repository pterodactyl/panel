<?php

namespace Pterodactyl\Http\Controllers\Base;

use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class IndexController extends Controller
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * IndexController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns listing of user's servers.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('templates/base.core');
    }
}
