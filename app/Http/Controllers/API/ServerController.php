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
    public function getServers(Request $request)
    {
        $servers = Models\Server::paginate(50);
        return $this->response->paginator($servers, new ServerTransformer);
    }

    /**
    * Create Server
    *
    * @Post("/servers")
    * @Versions({"v1"})
    * @Parameters({
    *      @Parameter("page", type="integer", description="The page of results to view.", default=1)
    * })
    * @Response(201)
     */
    public function postServer(Request $request)
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
        } catch (\Exception $e) {
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
    public function getServer(Request $request, $id)
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
            return $query->first();
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException('There was an issue with the fields passed in the request.');
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
    public function postServerSuspend(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->suspend($id);
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
    public function postServerUnsuspend(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->unsuspend($id);
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
    public function deleteServer(Request $request, $id, $force = null)
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
