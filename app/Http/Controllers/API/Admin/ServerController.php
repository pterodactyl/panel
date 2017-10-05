<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\API\Admin;

use Log;
use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\ServerRepository;
use Pterodactyl\Transformers\Admin\ServerTransformer;
use Pterodactyl\Exceptions\DisplayValidationException;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class ServerController extends Controller
{
    /**
     * Controller to handle returning all servers on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('server-list', $request->apiKey());

        $servers = Server::paginate(config('pterodactyl.paginate.api.servers'));
        $fractal = Fractal::create()->collection($servers)
            ->transformWith(new ServerTransformer($request))
            ->withResourceName('user')
            ->paginateWith(new IlluminatePaginatorAdapter($servers));

        if (config('pterodactyl.api.include_on_list') && $request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->toArray();
    }

    /**
     * Controller to handle returning information on a single server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return array
     */
    public function view(Request $request, $id)
    {
        $this->authorize('server-view', $request->apiKey());

        $server = Server::findOrFail($id);
        $fractal = Fractal::create()->item($server);

        if ($request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->transformWith(new ServerTransformer($request))
            ->withResourceName('server')
            ->toArray();
    }

    /**
     * Create a new server on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|array
     */
    public function store(Request $request)
    {
        $this->authorize('server-create', $request->apiKey());

        $repo = new ServerRepository;
        try {
            $server = $repo->create($request->all());

            $fractal = Fractal::create()->item($server)->transformWith(new ServerTransformer($request));
            if ($request->input('include')) {
                $fractal->parseIncludes(explode(',', $request->input('include')));
            }

            return $fractal->withResourceName('server')->toArray();
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage()),
            ], 400);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (TransferException $ex) {
            Log::warning($ex);

            return response()->json([
                'error' => 'A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.',
            ], 504);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to add this server. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete a server from the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        $this->authorize('server-delete', $request->apiKey());

        $repo = new ServerRepository;
        try {
            $repo->delete($id, $request->has('force_delete'));

            return response('', 204);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (TransferException $ex) {
            Log::warning($ex);

            return response()->json([
                'error' => 'A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.',
            ], 504);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to add this server. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the details for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\JsonResponse|array
     */
    public function details(Request $request, $id)
    {
        $this->authorize('server-edit-details', $request->apiKey());

        $repo = new ServerRepository;
        try {
            $server = $repo->updateDetails($id, $request->intersect([
                'owner_id', 'name', 'description', 'reset_token',
            ]));

            $fractal = Fractal::create()->item($server)->transformWith(new ServerTransformer($request));
            if ($request->input('include')) {
                $fractal->parseIncludes(explode(',', $request->input('include')));
            }

            return $fractal->withResourceName('server')->toArray();
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage()),
            ], 400);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to modify this server. Please try again.',
            ], 500);
        }
    }

    /**
     * Set the new docker container for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\RedirectResponse|array
     */
    public function container(Request $request, $id)
    {
        $this->authorize('server-edit-container', $request->apiKey());

        $repo = new ServerRepository;
        try {
            $server = $repo->updateContainer($id, $request->intersect('docker_image'));

            $fractal = Fractal::create()->item($server)->transformWith(new ServerTransformer($request));
            if ($request->input('include')) {
                $fractal->parseIncludes(explode(',', $request->input('include')));
            }

            return $fractal->withResourceName('server')->toArray();
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage()),
            ], 400);
        } catch (TransferException $ex) {
            Log::warning($ex);

            return response()->json([
                'error' => 'A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.',
            ], 504);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to modify this server container. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggles the install status for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function install(Request $request, $id)
    {
        $this->authorize('server-install', $request->apiKey());

        $repo = new ServerRepository;
        try {
            $repo->toggleInstall($id);

            return response('', 204);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to toggle the install status for this server. Please try again.',
            ], 500);
        }
    }

    /**
     * Setup a server to have a container rebuild.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function rebuild(Request $request, $id)
    {
        $this->authorize('server-rebuild', $request->apiKey());
        $server = Server::with('node')->findOrFail($id);

        try {
            $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('POST', '/server/rebuild');

            return response('', 204);
        } catch (TransferException $ex) {
            Log::warning($ex);

            return response()->json([
                'error' => 'A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.',
            ], 504);
        }
    }

    /**
     * Manage the suspension status for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function suspend(Request $request, $id)
    {
        $this->authorize('server-suspend', $request->apiKey());

        $repo = new ServerRepository;
        $action = $request->input('action');
        if (! in_array($action, ['suspend', 'unsuspend'])) {
            return response()->json([
                'error' => 'The action provided was invalid. Action should be one of: suspend, unsuspend.',
            ], 400);
        }

        try {
            $repo->toggleAccess($id, ($action === 'unsuspend'));

            return response('', 204);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (TransferException $ex) {
            Log::warning($ex);

            return response()->json([
                'error' => 'A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.',
            ], 504);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to ' . $action . ' this server. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the build configuration for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\JsonResponse|array
     */
    public function build(Request $request, $id)
    {
        $this->authorize('server-edit-build', $request->apiKey());

        $repo = new ServerRepository;
        try {
            $server = $repo->changeBuild($id, $request->intersect([
                'allocation_id', 'add_allocations', 'remove_allocations',
                'memory', 'swap', 'io', 'cpu',
            ]));

            $fractal = Fractal::create()->item($server)->transformWith(new ServerTransformer($request));
            if ($request->input('include')) {
                $fractal->parseIncludes(explode(',', $request->input('include')));
            }

            return $fractal->withResourceName('server')->toArray();
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage()),
            ], 400);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (TransferException $ex) {
            Log::warning($ex);

            return response()->json([
                'error' => 'A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.',
            ], 504);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to modify the build settings for this server. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the startup command as well as variables.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function startup(Request $request, $id)
    {
        $this->authorize('server-edit-startup', $request->apiKey());

        $repo = new ServerRepository;
        try {
            $repo->updateStartup($id, $request->all(), true);

            return response('', 204);
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage()),
            ], 400);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (TransferException $ex) {
            Log::warning($ex);

            return response()->json([
                'error' => 'A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.',
            ], 504);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to modify the startup settings for this server. Please try again.',
            ], 500);
        }
    }
}
