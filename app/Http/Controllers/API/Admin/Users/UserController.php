<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Users;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\Admin\UserTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @SWG\Swagger(
 *      schemes={"https"},
 *      basePath="/api/admin/users"
 * )
 */
class UserController extends Controller
{
    /**
     * @var \Spatie\Fractal\Fractal
     */
    private $fractal;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * UserController constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Spatie\Fractal\Fractal                                   $fractal
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        ConfigRepository $config,
        Fractal $fractal,
        UserRepositoryInterface $repository
    ) {
        $this->fractal = $fractal;
        $this->repository = $repository;
        $this->config = $config;
    }

    /**
     * Handle request to list all users on the panel.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $users = $this->repository->all($this->config->get('pterodactyl.paginate.api.users'));

        $fractal = $this->fractal->collection($users)
            ->transformWith(new UserTransformer($request))
            ->withResourceName('user')
            ->paginateWith(new IlluminatePaginatorAdapter($users));

        if ($this->config->get('pterodactyl.api.include_on_list') && $request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->toArray();
    }
}
