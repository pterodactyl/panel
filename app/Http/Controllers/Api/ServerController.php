<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends Controller
{
    public const ALLOWED_INCLUDES = ['product', 'user'];

    public const ALLOWED_FILTERS = ['name', 'suspended', 'identifier', 'pterodactyl_id', 'user_id', 'product_id'];

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $query = QueryBuilder::for(Server::class)
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->allowedFilters(self::ALLOWED_FILTERS);

        return $query->paginate($request->input('per_page') ?? 50);
    }

    /**
     * Display the specified resource.
     *
     * @param  Server  $server
     * @return Server|Collection|Model
     */
    public function show(Server $server)
    {
        $query = QueryBuilder::for(Server::class)
            ->where('id', '=', $server->id)
            ->allowedIncludes(self::ALLOWED_INCLUDES);

        return $query->firstOrFail();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Server  $server
     * @return Server
     */
    public function destroy(Server $server)
    {
        $server->delete();

        return $server;
    }

    /**
     * suspend server
     *
     * @param  Server  $server
     * @return Server|JsonResponse
     */
    public function suspend(Server $server)
    {
        try {
            $server->suspend();
        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return $server->load('product');
    }

    /**
     * unsuspend server
     *
     * @param  Server  $server
     * @return Server|JsonResponse
     */
    public function unSuspend(Server $server)
    {
        try {
            $server->unSuspend();
        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return $server->load('product');
    }
}
