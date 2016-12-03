<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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

use Illuminate\Http\Request;

use Pterodactyl\Models;
use Pterodactyl\Transformers\NodeTransformer;
use Pterodactyl\Transformers\AllocationTransformer;
use Pterodactyl\Repositories\NodeRepository;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @Resource("Servers")
 */
class NodeController extends BaseController
{

    public function __construct()
    {
        //
    }

    /**
     * List All Nodes
     *
     * Lists all nodes currently on the system.
     *
     * @Get("/nodes/{?page}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("page", type="integer", description="The page of results to view.", default=1)
     * })
     * @Response(200)
     */
    public function list(Request $request)
    {
        return Models\Node::all()->toArray();
    }

    /**
     * Create a New Node
     *
     * @Post("/nodes")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *      	'name' => 'My API Node',
     *      	'location' => 1,
     *      	'public' => 1,
     *      	'fqdn' => 'daemon.wuzzle.woo',
     *      	'scheme' => 'https',
     *      	'memory' => 10240,
     *      	'memory_overallocate' => 100,
     *      	'disk' => 204800,
     *      	'disk_overallocate' => -1,
     *      	'daemonBase' => '/srv/daemon-data',
     *      	'daemonSFTP' => 2022,
     *      	'daemonListen' => 8080
     *      }, headers={"Authorization": "Bearer <jwt-token>"}),
     *       @Response(200),
     *       @Response(422, body={
     *          "message": "A validation error occured.",
     *          "errors": {},
     *          "status_code": 422
     *       }),
     *       @Response(503, body={
     *       	"message": "There was an error while attempting to add this node to the system.",
     *       	"status_code": 503
     *       })
     * })
     */
    public function create(Request $request)
    {
        try {
            $node = new NodeRepository;
            $new = $node->create($request->all());
            return [ 'id' => $new ];
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('There was an error while attempting to add this node to the system.');
        }
    }

    /**
     * List Specific Node
     *
     * Lists specific fields about a server or all fields pertaining to that node.
     *
     * @Get("/nodes/{id}/{?fields}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the node to get information on."),
     *      @Parameter("fields", type="string", required=false, description="A comma delimidated list of fields to include.")
     * })
     * @Response(200)
     */
    public function view(Request $request, $id, $fields = null)
    {
        $node = Models\Node::where('id', $id);

        if (!is_null($request->input('fields'))) {
            foreach(explode(',', $request->input('fields')) as $field) {
                if (!empty($field)) {
                    $node->addSelect($field);
                }
            }
        }

        try {
            if (!$node->first()) {
                throw new NotFoundHttpException('No node by that ID was found.');
            }

            return [
                'node' => $node->first(),
                'allocations' => [
                    'assigned' => Models\Allocation::where('node', $id)->whereNotNull('assigned_to')->get(),
                    'unassigned' => Models\Allocation::where('node', $id)->whereNull('assigned_to')->get()
                ]
            ];
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException('There was an issue with the fields passed in the request.');
        }
    }

    public function config(Request $request, $id)
    {
        if (!$request->secure()) {
            throw new BadRequestHttpException('This API route can only be accessed using a secure connection.');
        }

        $node = Models\Node::where('id', $id)->first();
        if (!$node) {
            throw new NotFoundHttpException('No node by that ID was found.');
        }

        return [
            'web' => [
                'listen' => $node->daemonListen,
                'ssl' => [
                    'enabled' => ($node->scheme === 'https'),
                    'certificate' => '/etc/certs/' . $node->fqdn . '/fullchain.pem',
                    'key' => '/etc/certs/' . $node->fqdn . '/privkey.pem'
                ]
            ],
            'docker' => [
                'socket' => '/var/run/docker.sock',
                'autoupdate_images' => true
            ],
            'sftp' => [
                'path' => $node->daemonBase,
                'port' => (int) $node->daemonSFTP,
                'container' => '0x0000'
            ],
            'logger' => [
                'path' => 'logs/',
                'src' => false,
                'level' => 'info',
                'period' => '1d',
                'count' => 3
            ],
            'remote' => [
                'download' => route('remote.download'),
                'installed' => route('remote.install')
            ],
            'uploads' => [
                'maximumSize' => 100000000
            ],
            'keys' => [
                $node->daemonSecret
            ],
            'query' => [
                'kill_on_fail' => true,
                'fail_limit' => 3
            ]
        ];
    }

    /**
     * List all Node Allocations
     *
     * Returns a listing of all allocations for every node.
     *
     * @Get("/nodes/allocations")
     * @Versions({"v1"})
     * @Response(200)
     */
     public function allocations(Request $request)
     {
         $allocations = Models\Allocation::all();
         if ($allocations->count() < 1) {
             throw new NotFoundHttpException('No allocations have been created.');
         }
         return $allocations;
     }
	 
    /**
     * List Node Allocation based on assigned to ID
     *
     * Returns a listing of the allocation for the specified server id.
     *
     * @Get("/nodes/allocations/{id}")
     * @Versions({"v1"})
     * @Response(200)
     */
     public function allocationsView(Request $request, $id)
     {
        $query = Models\Allocation::where('assigned_to', $id);
        try {
			
			$allocations = $query->get();

            if (!$allocations->first()) {
                throw new NotFoundHttpException('No allocations for that server were found.');
            }
    
            return $allocations;
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException('There was an issue with the fields passed in the request.');
        }
    }

    /**
     * Delete Node
     *
     * @Delete("/nodes/{id}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the node."),
     * })
     * @Response(204)
     */
    public function delete(Request $request, $id)
    {
        try {
            $node = new NodeRepository;
            $node->delete($id);
            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch(\Exception $e) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to delete this node.');
        }
    }

}
