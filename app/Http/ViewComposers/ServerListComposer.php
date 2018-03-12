<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class ServerListComposer
{
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ServerListComposer constructor.
     *
     * @param \Illuminate\Http\Request                                    $request
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(Request $request, ServerRepositoryInterface $repository)
    {
        $this->request = $request;
        $this->repository = $repository;
    }

    /**
     * Attach a list of servers the user can access to the view.
     *
     * @param \Illuminate\View\View $view
     */
    public function compose(View $view)
    {
        if (! $this->request->user()) {
            return;
        }

        $servers = $this->repository
            ->setColumns(['id', 'owner_id', 'uuidShort', 'name', 'description'])
            ->filterUserAccessServers($this->request->user(), User::FILTER_LEVEL_SUBUSER, false);

        $view->with('sidebarServerList', $servers);
    }
}
