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
use Pterodactyl\Models\Server;
use Dingo\Api\Exception\ResourceException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\ServerRepository;
use Pterodactyl\Exceptions\DisplayValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ServerController extends BaseController
{
    /**
     * Lists all servers currently on the system.
     *
     * @param  Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        return Server::all()->toArray();
    }

    /**
     * Create Server.
     *
     * @param  Request  $request
     * @return array
     */
    public function create(Request $request)
    {
        $repo = new ServerRepository;

        try {
            $server = $repo->create($request->all());

            return ['id' => $server->id];
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
     * List Specific Server.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return array
     */
    public function view(Request $request, $id)
    {
        $server = Server::with('node', 'allocations', 'pack')->where('id', $id)->firstOrFail();

        if (! is_null($request->input('fields'))) {
            $fields = explode(',', $request->input('fields'));
            if (! empty($fields) && is_array($fields)) {
                return collect($server)->only($fields);
            }
        }

        if ($request->input('daemon') === 'true') {
            try {
                $response = $server->node->guzzleClient([
                    'X-Access-Token' => $server->node->daemonSecret,
                ])->request('GET', '/servers');

                $server->daemon = json_decode($response->getBody())->{$server->uuid};
            } catch (\GuzzleHttp\Exception\TransferException $ex) {
                // Couldn't hit the daemon, return what we have though.
                $server->daemon = [
                    'error' => 'There was an error encountered while attempting to connect to the remote daemon.',
                ];
            }
        }

        $server->allocations->transform(function ($item) {
            return collect($item)->except(['created_at', 'updated_at']);
        });

        return $server->toArray();
    }

    /**
     * Update Server configuration.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return array
     */
    public function config(Request $request, $id)
    {
        $repo = new ServerRepository;

        try {
            $server = $repo->updateDetails($id, $request->intersect([
                'owner_id', 'name', 'reset_token',
            ]));

            return ['id' => $id];
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to update server on system due to an error.');
        }
    }

    /**
     * Update Server Build Configuration.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return array
     */
    public function build(Request $request, $id)
    {
        $repo = new ServerRepository;

        try {
            $server = $repo->changeBuild($id, $request->intersect([
                'allocation_id', 'add_allocations', 'remove_allocations',
                'memory', 'swap', 'io', 'cpu',
            ]));

            return ['id' => $id];
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to update server on system due to an error.');
        }
    }

    /**
     * Suspend Server.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return void
     */
    public function suspend(Request $request, $id)
    {
        try {
            $repo = new ServerRepository;
            $repo->suspend($id);

            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to suspend this server instance.');
        }
    }

    /**
     * Unsuspend Server.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return void
     */
    public function unsuspend(Request $request, $id)
    {
        try {
            $repo = new ServerRepository;
            $repo->unsuspend($id);

            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to unsuspend this server instance.');
        }
    }

    /**
     * Delete Server.
     *
     * @param  Request  $request
     * @param  int      $id
     * @param  string|null $force
     * @return void
     */
    public function delete(Request $request, $id, $force = null)
    {
        $repo = new ServerRepository;

        try {
            $repo->deleteServer($id, $force);

            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $e) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to delete this server.');
        }
    }
}
