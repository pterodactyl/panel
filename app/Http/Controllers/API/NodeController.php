<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\API;

use Log;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Allocation;
use Dingo\Api\Exception\ResourceException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\NodeRepository;
use Pterodactyl\Exceptions\DisplayValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class NodeController extends BaseController
{
    /**
     * Lists all nodes currently on the system.
     *
     * @param  Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        return Node::all()->toArray();
    }

    /**
     * Create a new node.
     *
     * @param  Request $request
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create(Request $request)
    {
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

            return ['id' => $node->id];
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            Log::error($ex);
            throw new BadRequestHttpException('There was an error while attempting to add this node to the system. This error has been logged.');
        }
    }

    /**
     * Lists specific fields about a server or all fields pertaining to that node.
     *
     * @param  Request  $request
     * @param  int      $id
     * @param  string   $fields
     * @return array
     */
    public function view(Request $request, $id, $fields = null)
    {
        $node = Node::with('allocations')->findOrFail($id);

        $node->allocations->transform(function ($item) {
            return collect($item)->only([
                'id', 'ip', 'ip_alias', 'port', 'server_id',
            ]);
        });

        if (! empty($request->input('fields'))) {
            $fields = explode(',', $request->input('fields'));
            if (! empty($fields) && is_array($fields)) {
                return collect($node)->only($fields);
            }
        }

        return $node->toArray();
    }

     /**
      * Returns a configuration file for a given node.
      *
      * @param  Request $request
      * @param  int     $id
      * @return array
      */
    public function config(Request $request, $id)
    {
        $node = Node::findOrFail($id);

        return $node->getConfigurationAsJson();
    }

     /**
      * Returns a listing of all allocations for every node.
      *
      * @param  Request $request
      * @return array
      */
     public function allocations(Request $request)
     {
         return Allocation::all()->toArray();
     }

     /**
      * Returns a listing of the allocation for the specified server id.
      *
      * @param  Request $request
      * @return array
      */
     public function allocationsView(Request $request, $id)
     {
         return Allocation::where('server_id', $id)->get()->toArray();
     }

    /**
     * Delete a node.
     *
     * @param  Request $request
     * @param  int     $id
     * @return void
     */
    public function delete(Request $request, $id)
    {
        $repo = new NodeRepository;
        try {
            $repo->delete($id);

            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $e) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to delete this node.');
        }
    }
}
