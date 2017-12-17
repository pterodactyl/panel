<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Nodes;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\Api\Admin\NodeTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeController extends Controller
{
    /**
     * @var \Spatie\Fractal\Fractal
     */
    private $fractal;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * NodeController constructor.
     *
     * @param \Spatie\Fractal\Fractal                                   $fractal
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(Fractal $fractal, NodeRepositoryInterface $repository)
    {
        $this->fractal = $fractal;
        $this->repository = $repository;
    }

    /**
     * Return all of the nodes currently available on the Panel.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $nodes = $this->repository->all(config('pterodactyl.paginate.api.nodes'));

        $fractal = $this->fractal->collection($nodes)
            ->transformWith(new NodeTransformer($request))
            ->withResourceName('node')
            ->paginateWith(new IlluminatePaginatorAdapter($nodes));

        return $fractal->toArray();
    }

    /**
     * Return data for a single instance of a node.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Node $node
     * @return array
     */
    public function view(Request $request, Node $node): array
    {
        $fractal = $this->fractal->item($node)
            ->transformWith(new NodeTransformer($request))
            ->withResourceName('node');

        return $fractal->toArray();
    }
}
