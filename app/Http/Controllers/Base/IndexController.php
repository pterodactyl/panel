<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $servers = $this->repository->setSearchTerm($request->input('query'))->filterUserAccessServers(
            $request->user(), User::FILTER_LEVEL_ALL, config('pterodactyl.paginate.frontend.servers')
        );

        return view('templates/base.core', ['servers' => $servers]);
    }
}
