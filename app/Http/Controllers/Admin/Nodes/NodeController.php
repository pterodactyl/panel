<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Contracts\View\Factory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\NodeRepository;

class NodeController extends Controller
{
    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private $view;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $repository;

    /**
     * NodeController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\NodeRepository $repository
     * @param \Illuminate\Contracts\View\Factory $view
     */
    public function __construct(NodeRepository $repository, Factory $view)
    {
        $this->view = $view;
        $this->repository = $repository;
    }

    /**
     * Returns a listing of nodes on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $nodes = QueryBuilder::for(
            Node::query()->with('location')->withCount('servers')
        )
            ->allowedFilters(['uuid', 'name'])
            ->allowedSorts(['id'])
            ->paginate(25);

        return $this->view->make('admin.nodes.index', ['nodes' => $nodes]);
    }
}
