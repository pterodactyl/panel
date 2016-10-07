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

use Log;
use Pterodactyl\Models;
use Pterodactyl\Transformers\ServerTransformer;
use Pterodactyl\Repositories\ServerRepository;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @Resource("Servers")
 */
class ServerController extends BaseController
{

    public function __construct()
    {
        //
    }

    /**
     * List All Servers
     *
     * Lists all servers currently on the system.
     *
     * @Get("/servers/{?page}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("page", type="integer", description="The page of results to view.", default=1)
     * })
     * @Response(200)
     */
    public function list(Request $request)
    {
        return Models\Server::all()->toArray();
    }

    /**
    * Create Server
    *
    * @Post("/servers")
    * @Versions({"v1"})
    * @Response(201)
     */
    public function create(Request $request)
    {
        try {
            $server = new ServerRepository;
            $new = $server->create($request->all());
            return $this->response->created(route('api.servers.view', [
                'id' => $new
            ]));
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            Log::error($ex);
            throw new BadRequestHttpException('There was an error while attempting to add this server to the system.');
        }
    }

    /**
     * List Specific Server
     *
     * Lists specific fields about a server or all fields pertaining to that server.
     *
     * @Get("/servers/{id}{?fields}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the server to get information on."),
     *      @Parameter("fields", type="string", required=false, description="A comma delimidated list of fields to include.")
     * })
     * @Response(200)
     */
    public function view(Request $request, $id)
    {
        $query = Models\Server::where('id', $id);

        if (!is_null($request->input('fields'))) {
            foreach(explode(',', $request->input('fields')) as $field) {
                if (!empty($field)) {
                    $query->addSelect($field);
                }
            }
        }

        try {
            if (!$query->first()) {
                throw new NotFoundHttpException('No server by that ID was found.');
            }

            // Requested Daemon Stats
            $server = $query->first();
            if ($request->input('daemon') === 'true') {
                $node = Models\Node::findOrFail($server->node);
                $client = Models\Node::guzzleRequest($node->id);

                $response = $client->request('GET', '/servers', [
                    'headers' => [
                        'X-Access-Token' => $node->daemonSecret
                    ]
                ]);

                // Only return the daemon token if the request is using HTTPS
                if ($request->secure()) {
                    $server->daemon_token = $server->daemonSecret;
                }
                $server->daemon = json_decode($response->getBody())->{$server->uuid};

                return $server->toArray();
            }

            return $server->toArray();

        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            // Couldn't hit the daemon, return what we have though.
            $server->daemon = [
                'error' => 'There was an error encountered while attempting to connect to the remote daemon.'
            ];
            return $server->toArray();
        } catch (\Exception $ex) {
            throw new BadRequestHttpException('There was an issue with the fields passed in the request.');
        }
    }

    /**
     * Update Server configuration
     *
     * Updates display information on panel.
     *
     * @Patch("/servers/{id}/config")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *          "owner": "new@email.com",
     *          "name": "New Name",
     *          "reset_token": true
     *      }, headers={"Authorization": "Bearer <token>"}),
     *      @Response(200, body={"name": "New Name"}),
     *      @Response(422)
     * })
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the server to modify.")
     * })
     */
    public function config(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->updateDetails($id, $request->all());
            return Models\Server::findOrFail($id);
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to update server on system due to an error.');
        }
    }

    /**
     * Update Server Build Configuration
     *
     * Updates server build information on panel and on node.
     *
     * @Patch("/servers/{id}/build")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *          "default": "192.168.0.1:25565",
     *          "add_additional": [
     *              "192.168.0.1:25566",
     *              "192.168.0.1:25567",
     *              "192.168.0.1:25568"
     *          ],
     *          "remove_additional": [],
     *          "memory": 1024,
     *          "swap": 0,
     *          "io": 500,
     *          "cpu": 0,
     *          "disk": 1024
     *      }, headers={"Authorization": "Bearer <token>"}),
     *      @Response(200, body={"name": "New Name"}),
     *      @Response(422)
     * })
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the server to modify.")
     * })
     */
    public function build(Request $request, $id)
    {
        try {
            throw new BadRequestHttpException('There was an error while attempting to add this node to the system.');

            $server = new ServerRepository;
            $server->changeBuild($id, $request->all());
            return Models\Server::findOrFail($id);
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to update server on system due to an error.');
        }
    }

    /**
     * Suspend Server
     *
     * @Post("/servers/{id}/suspend")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the server."),
     * })
     * @Response(204)
     */
    public function suspend(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->suspend($id);
            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to suspend this server instance.');
        }
    }

    /**
     * Unsuspend Server
     *
     * @Post("/servers/{id}/unsuspend")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the server."),
     * })
     * @Response(204)
     */
    public function unsuspend(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->unsuspend($id);
            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to unsuspend this server instance.');
        }
    }

    /**
     * Delete Server
     *
     * @Delete("/servers/{id}/{force}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the server."),
     *      @Parameter("force", type="string", required=false, description="Use 'force' if the server should be removed regardless of daemon response."),
     * })
     * @Response(204)
     */
    public function delete(Request $request, $id, $force = null)
    {
        try {
            $server = new ServerRepository;
            $server->deleteServer($id, $force);
            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch(\Exception $e) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to delete this server.');
        }
    }

}
