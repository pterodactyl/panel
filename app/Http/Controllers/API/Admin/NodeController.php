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
use Pterodactyl\Models\Node;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\NodeRepository;
use Pterodactyl\Transformers\Admin\NodeTransformer;
use Pterodactyl\Exceptions\DisplayValidationException;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class NodeController extends Controller
{
    /**
     * Controller to handle returning all nodes on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('node-list', $request->apiKey());

        $nodes = Node::paginate(config('pterodactyl.paginate.api.nodes'));
        $fractal = Fractal::create()->collection($nodes)
            ->transformWith(new NodeTransformer($request))
            ->withResourceName('user')
            ->paginateWith(new IlluminatePaginatorAdapter($nodes));

        if (config('pterodactyl.api.include_on_list') && $request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->toArray();
    }

    /**
     * Display information about a single node on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return array
     */
    public function view(Request $request, $id)
    {
        $this->authorize('node-view', $request->apiKey());

        $fractal = Fractal::create()->item(Node::findOrFail($id));
        if ($request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->transformWith(new NodeTransformer($request))
            ->withResourceName('node')
            ->toArray();
    }

    /**
     * Display information about a single node on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewConfig(Request $request, $id)
    {
        $this->authorize('node-view-config', $request->apiKey());

        $node = Node::findOrFail($id);

        return response()->json(json_decode($node->getConfigurationAsJson()));
    }

    /**
     * Create a new node on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|array
     */
    public function store(Request $request)
    {
        $this->authorize('node-create', $request->apiKey());

        $repo = new NodeRepository;
        try {
            $node = $repo->create(array_merge(
                $request->only([
                    'public', 'disk_overallocate', 'memory_overallocate',
                ]),
                $request->intersect([
                    'name', 'location_id', 'fqdn',
                    'scheme', 'memory', 'disk',
                    'daemonBase', 'daemonSFTP', 'daemonListen',
                ])
            ));

            $fractal = Fractal::create()->item($node)->transformWith(new NodeTransformer($request));
            if ($request->input('include')) {
                $fractal->parseIncludes(explode(',', $request->input('include')));
            }

            return $fractal->withResourceName('node')->toArray();
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
                'error' => 'An unhandled exception occured while attemping to create this node. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete a node from the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        $this->authorize('node-delete', $request->apiKey());

        $repo = new NodeRepository;
        try {
            $repo->delete($id);

            return response('', 204);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 400);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to delete this node. Please try again.',
            ], 500);
        }
    }
}
