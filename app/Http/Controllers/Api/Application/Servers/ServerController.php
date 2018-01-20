<?php

namespace Pterodactyl\Http\Controllers\Api\Admin;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\Api\Admin\ServerTransformer;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class ServerController extends Controller
{
    /**
     * @var \Spatie\Fractal\Fractal
     */
    private $fractal;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ServerController constructor.
     *
     * @param \Spatie\Fractal\Fractal                                     $fractal
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(Fractal $fractal, ServerRepositoryInterface $repository)
    {
        $this->fractal = $fractal;
        $this->repository = $repository;
    }

    public function index(Request $request): array
    {
        $servers = $this->repository->paginated(50);

        return $this->fractal->collection($servers)
            ->transformWith((new ServerTransformer)->setKey())
            ->withResourceName('server')
            ->toArray();
    }
}
