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

namespace Pterodactyl\Repositories;

use DB;
use Log;
use Crypt;
use Validator;
use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Services\DeploymentService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServerRepository
{
    protected $daemonPermissions = [
        's:*',
    ];

    public function __construct()
    {
        //
    }

    /**
     * Generates a SFTP username for a server given a server name.
     * format: mumble_67c7a4b0.
     *
     * @param  string $name
     * @param  string $identifier
     * @return string
     */
    protected function generateSFTPUsername($name, $identifier = null)
    {
        if (is_null($identifier) || ! ctype_alnum($identifier)) {
            $unique = str_random(8);
        } else {
            if (strlen($identifier) < 8) {
                $unique = $identifier . str_random((8 - strlen($identifier)));
            } else {
                $unique = substr($identifier, 0, 8);
            }
        }

        // Filter the Server Name
        $name = trim(preg_replace('/[^\w]+/', '', $name), '_');
        $name = (strlen($name) < 1) ? str_random(6) : $name;

        return strtolower(substr($name, 0, 6) . '_' . $unique);
    }

    /**
     * Adds a new server to the system.
     * @param   array  $data  An array of data descriptors for creating the server. These should align to the columns in the database.
     * @return  int
     */
    public function create(array $data)
    {

        // Validate Fields
        $validator = Validator::make($data, [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|regex:/^([\w .-]{1,200})$/',
            'memory' => 'required|numeric|min:0',
            'swap' => 'required|numeric|min:-1',
            'io' => 'required|numeric|min:10|max:1000',
            'cpu' => 'required|numeric|min:0',
            'disk' => 'required|numeric|min:0',
            'service_id' => 'required|numeric|min:1|exists:services,id',
            'option_id' => 'required|numeric|min:1|exists:service_options,id',
            'location_id' => 'required|numeric|min:1|exists:locations,id',
            'pack_id' => 'sometimes|nullable|numeric|min:0',
            'startup' => 'string',
            'auto_deploy' => 'sometimes|boolean',
            'custom_id' => 'sometimes|required|numeric|unique:servers,id',
        ]);

        $validator->sometimes('node_id', 'required|numeric|min:1|exists:nodes,id', function ($input) {
            return ! ($input->auto_deploy);
        });

        $validator->sometimes('allocation_id', 'required|numeric|exists:allocations,id', function ($input) {
            return ! ($input->auto_deploy);
        });

        $validator->sometimes('allocation_additional.*', 'sometimes|required|numeric|exists:allocations,id', function ($input) {
            return ! ($input->auto_deploy);
        });

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $user = Models\User::findOrFail($data['user_id']);

        $autoDeployed = false;
        if (isset($data['auto_deploy']) && $data['auto_deploy']) {
            // This is an auto-deployment situation
            // Ignore any other passed node data
            unset($data['node_id'], $data['allocation_id']);

            $autoDeployed = true;
            $node = DeploymentService::smartRandomNode($data['memory'], $data['disk'], $data['location_id']);
            $allocation = DeploymentService::randomAllocation($node->id);
        } else {
            $node = Models\Node::findOrFail($data['node_id']);
        }

        // Verify IP & Port are a.) free and b.) assigned to the node.
        // We know the node exists because of 'exists:nodes,id' in the validation
        if (! $autoDeployed) {
            $allocation = Models\Allocation::where('id', $data['allocation_id'])->where('node_id', $data['node_id'])->whereNull('server_id')->first();
        }

        // Something failed in the query, either that combo doesn't exist, or it is in use.
        if (! $allocation) {
            throw new DisplayException('The selected Allocation ID is either already in use, or unavaliable for this node.');
        }

        // Validate those Service Option Variables
        // We know the service and option exists because of the validation.
        // We need to verify that the option exists for the service, and then check for
        // any required variable fields. (fields are labeled env_<env_variable>)
        $option = Models\ServiceOption::where('id', $data['option_id'])->where('service_id', $data['service_id'])->first();
        if (! $option) {
            throw new DisplayException('The requested service option does not exist for the specified service.');
        }

        // Validate the Pack
        if (! isset($data['pack_id']) || (int) $data['pack_id'] < 1) {
            $data['pack_id'] = null;
        } else {
            $pack = Models\Pack::where('id', $data['pack_id'])->where('option_id', $data['option_id'])->first();
            if (! $pack) {
                throw new DisplayException('The requested service pack does not seem to exist for this combination.');
            }
        }

        // Load up the Service Information
        $service = Models\Service::find($option->service_id);

        // Check those Variables
        $variables = Models\ServiceVariable::where('option_id', $data['option_id'])->get();
        $variableList = [];
        if ($variables) {
            foreach ($variables as $variable) {

                // Is the variable required?
                if (! isset($data['env_' . $variable->env_variable])) {
                    if ($variable->required) {
                        throw new DisplayException('A required service option variable field (env_' . $variable->env_variable . ') was missing from the request.');
                    }
                    $variableList[] = [
                        'id' => $variable->id,
                        'env' => $variable->env_variable,
                        'val' => $variable->default_value,
                    ];
                    continue;
                }

                // Check aganist Regex Pattern
                if (! is_null($variable->regex) && ! preg_match($variable->regex, $data['env_' . $variable->env_variable])) {
                    throw new DisplayException('Failed to validate service option variable field (env_' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                }

                $variableList[] = [
                    'id' => $variable->id,
                    'env' => $variable->env_variable,
                    'val' => $data['env_' . $variable->env_variable],
                ];
                continue;
            }
        }

        // Check Overallocation
        if (! $autoDeployed) {
            if (is_numeric($node->memory_overallocate) || is_numeric($node->disk_overallocate)) {
                $totals = Models\Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node_id', $node->id)->first();

                // Check memory limits
                if (is_numeric($node->memory_overallocate)) {
                    $newMemory = $totals->memory + $data['memory'];
                    $memoryLimit = ($node->memory * (1 + ($node->memory_overallocate / 100)));
                    if ($newMemory > $memoryLimit) {
                        throw new DisplayException('The amount of memory allocated to this server would put the node over its allocation limits. This node is allowed ' . ($node->memory_overallocate + 100) . '% of its assigned ' . $node->memory . 'Mb of memory (' . $memoryLimit . 'Mb) of which ' . (($totals->memory / $node->memory) * 100) . '% (' . $totals->memory . 'Mb) is in use already. By allocating this server the node would be at ' . (($newMemory / $node->memory) * 100) . '% (' . $newMemory . 'Mb) usage.');
                    }
                }

                // Check Disk Limits
                if (is_numeric($node->disk_overallocate)) {
                    $newDisk = $totals->disk + $data['disk'];
                    $diskLimit = ($node->disk * (1 + ($node->disk_overallocate / 100)));
                    if ($newDisk > $diskLimit) {
                        throw new DisplayException('The amount of disk allocated to this server would put the node over its allocation limits. This node is allowed ' . ($node->disk_overallocate + 100) . '% of its assigned ' . $node->disk . 'Mb of disk (' . $diskLimit . 'Mb) of which ' . (($totals->disk / $node->disk) * 100) . '% (' . $totals->disk . 'Mb) is in use already. By allocating this server the node would be at ' . (($newDisk / $node->disk) * 100) . '% (' . $newDisk . 'Mb) usage.');
                    }
                }
            }
        }

        DB::beginTransaction();

        try {
            $uuid = new UuidService;

            // Add Server to the Database
            $server = new Models\Server;
            $genUuid = $uuid->generate('servers', 'uuid');
            $genShortUuid = $uuid->generateShort('servers', 'uuidShort', $genUuid);

            if (isset($data['custom_id'])) {
                $server->id = $data['custom_id'];
            }

            $server->fill([
                'uuid' => $genUuid,
                'uuidShort' => $genShortUuid,
                'node_id' => $node->id,
                'name' => $data['name'],
                'suspended' => 0,
                'owner_id' => $user->id,
                'memory' => $data['memory'],
                'swap' => $data['swap'],
                'disk' => $data['disk'],
                'io' => $data['io'],
                'cpu' => $data['cpu'],
                'oom_disabled' => (isset($data['oom_disabled'])) ? true : false,
                'allocation_id' => $allocation->id,
                'service_id' => $data['service_id'],
                'option_id' => $data['option_id'],
                'pack_id' => $data['pack_id'],
                'startup' => $data['startup'],
                'daemonSecret' => $uuid->generate('servers', 'daemonSecret'),
                'image' => (isset($data['custom_container'])) ? $data['custom_container'] : $option->docker_image,
                'username' => $this->generateSFTPUsername($data['name'], $genShortUuid),
                'sftp_password' => Crypt::encrypt('not set'),
            ]);
            $server->save();

            // Mark Allocation in Use
            $allocation->server_id = $server->id;
            $allocation->save();

            // Add Additional Allocations
            if (isset($data['allocation_additional']) && is_array($data['allocation_additional'])) {
                foreach ($data['allocation_additional'] as $allocation) {
                    $model = Models\Allocation::where('id', $allocation)->where('node_id', $data['node_id'])->whereNull('server_id')->first();
                    if (! $model) {
                        continue;
                    }

                    $model->server_id = $server->id;
                    $model->save();
                }
            }

            // Add Variables
            $environmentVariables = [
                'STARTUP' => $data['startup'],
            ];

            foreach ($variableList as $item) {
                $environmentVariables[$item['env']] = $item['val'];

                Models\ServerVariable::create([
                    'server_id' => $server->id,
                    'variable_id' => $item['id'],
                    'variable_value' => $item['val'],
                ]);
            }

            $server->load('allocation', 'allocations');
            $node->guzzleClient(['X-Access-Token' => $node->daemonSecret])->request('POST', '/servers', [
                'json' => [
                    'uuid' => (string) $server->uuid,
                    'user' => $server->username,
                    'build' => [
                        'default' => [
                            'ip' => $server->allocation->ip,
                            'port' => $server->allocation->port,
                        ],
                        'ports' => $server->allocations->groupBy('ip')->map(function ($item) {
                            return $item->pluck('port');
                        })->toArray(),
                        'env' => $environmentVariables,
                        'memory' => (int) $server->memory,
                        'swap' => (int) $server->swap,
                        'io' => (int) $server->io,
                        'cpu' => (int) $server->cpu,
                        'disk' => (int) $server->disk,
                        'image' => (isset($data['custom_container'])) ? $data['custom_container'] : $option->docker_image,
                    ],
                    'service' => [
                        'type' => $service->file,
                        'option' => $option->tag,
                        'pack' => (isset($pack)) ? $pack->uuid : null,
                    ],
                    'keys' => [
                        (string) $server->daemonSecret => $this->daemonPermissions,
                    ],
                    'rebuild' => false,
                ],
            ]);

            DB::commit();

            return $server;
        } catch (TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error while attempting to connect to the daemon to add this server.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * [updateDetails description].
     * @param  int  $id
     * @param  array    $data
     * @return bool
     */
    public function updateDetails($id, array $data)
    {
        $uuid = new UuidService;
        $resetDaemonKey = false;

        // Validate Fields
        $validator = Validator::make($data, [
            'owner_id' => 'sometimes|required|integer|exists:users,id',
            'name' => 'sometimes|required|regex:([\w .-]{1,200})',
            'reset_token' => 'sometimes|required|accepted',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();

        try {
            $server = Models\Server::with('user')->findOrFail($id);

            // Update daemon secret if it was passed.
            if (isset($data['reset_token']) || (isset($data['owner_id']) && (int) $data['owner_id'] !== $server->user->id)) {
                $oldDaemonKey = $server->daemonSecret;
                $server->daemonSecret = $uuid->generate('servers', 'daemonSecret');
                $resetDaemonKey = true;
            }

            // Update Server Owner if it was passed.
            if (isset($data['owner_id']) && (int) $data['owner_id'] !== $server->user->id) {
                $server->owner_id = $data['owner_id'];
            }

            // Update Server Name if it was passed.
            if (isset($data['name'])) {
                $server->name = $data['name'];
            }

            // Save our changes
            $server->save();

            // Do we need to update? If not, return successful.
            if (! $resetDaemonKey) {
                DB::commit();

                return true;
            }

            $res = $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('PATCH', '/server', [
                'exceptions' => false,
                'json' => [
                    'keys' => [
                        (string) $oldDaemonKey => [],
                        (string) $server->daemonSecret => $this->daemonPermissions,
                    ],
                ],
            ]);

            if ($res->getStatusCode() === 204) {
                DB::commit();

                return true;
            } else {
                throw new DisplayException('Daemon returned a a non HTTP/204 error code. HTTP/' + $res->getStatusCode());
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex);
            throw new DisplayException('An error occured while attempting to update this server\'s information.');
        }
    }

    /**
     * [updateContainer description].
     * @param  int      $id
     * @param  array    $data
     * @return bool
     */
    public function updateContainer($id, array $data)
    {
        $validator = Validator::make($data, [
            'docker_image' => 'required|string',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();
        try {
            $server = Models\Server::findOrFail($id);

            $server->image = $data['docker_image'];
            $server->save();

            $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('PATCH', '/server', [
                'json' => [
                    'build' => [
                        'image' => $server->image,
                    ],
                ],
            ]);

            DB::commit();

            return true;
        } catch (TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('A TransferException occured while attempting to update the container image. Is the daemon online? This error has been logged.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * [changeBuild description].
     * @param  int  $id
     * @param  array    $data
     * @return bool
     */
    public function changeBuild($id, array $data)
    {
        $validator = Validator::make($data, [
            'allocation_id' => 'sometimes|required|exists:allocations,id',
            'add_allocations' => 'sometimes|required|array',
            'remove_allocations' => 'sometimes|required|array',
            'memory' => 'sometimes|required|integer|min:0',
            'swap' => 'sometimes|required|integer|min:-1',
            'io' => 'sometimes|required|integer|min:10|max:1000',
            'cpu' => 'sometimes|required|integer|min:0',
            'disk' => 'sometimes|required|integer|min:0',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();

        try {
            $server = Models\Server::with('allocation', 'allocations')->findOrFail($id);
            $newBuild = [];
            $newAllocations = [];

            if (isset($data['allocation_id'])) {
                if ((int) $data['allocation_id'] !== $server->allocation_id) {
                    $selection = $server->allocations->where('id', $data['allocation_id'])->first();
                    if (! $selection) {
                        throw new DisplayException('The requested default connection is not allocated to this server.');
                    }

                    $server->allocation_id = $selection->id;
                    $newBuild['default'] = ['ip' => $selection->ip, 'port' => $selection->port];

                    $server->load('allocation');
                }
            }

            $newPorts = false;
            // Remove Assignments
            if (isset($data['remove_allocations'])) {
                foreach ($data['remove_allocations'] as $allocation) {
                    // Can't remove the assigned IP/Port combo
                    if ((int) $allocation === $server->allocation_id) {
                        continue;
                    }

                    $newPorts = true;
                    Models\Allocation::where('id', $allocation)->where('server_id', $server->id)->update([
                        'server_id' => null,
                    ]);
                }

                $server->load('allocations');
            }

            // Add Assignments
            if (isset($data['add_allocations'])) {
                foreach ($data['add_allocations'] as $allocation) {
                    $model = Models\Allocation::where('id', $allocation)->whereNull('server_id')->first();
                    if (! $model) {
                        continue;
                    }

                    $newPorts = true;
                    $model->update([
                        'server_id' => $server->id,
                    ]);
                }

                $server->load('allocations');
            }

            if ($newPorts) {
                $newBuild['ports|overwrite'] = $server->allocations->groupBy('ip')->map(function ($item) {
                    return $item->pluck('port');
                })->toArray();
            }

            // @TODO: verify that server can be set to this much memory without
            // going over node limits.
            if (isset($data['memory']) && $server->memory !== (int) $data['memory']) {
                $server->memory = $data['memory'];
                $newBuild['memory'] = (int) $server->memory;
            }

            if (isset($data['swap']) && $server->swap !== (int) $data['swap']) {
                $server->swap = $data['swap'];
                $newBuild['swap'] = (int) $server->swap;
            }

            // @TODO: verify that server can be set to this much disk without
            // going over node limits.
            if (isset($data['disk']) && $server->disk !== (int) $data['disk']) {
                $server->disk = $data['disk'];
                $newBuild['disk'] = (int) $server->disk;
            }

            if (isset($data['cpu']) && $server->cpu !== (int) $data['cpu']) {
                $server->cpu = $data['cpu'];
                $newBuild['cpu'] = (int) $server->cpu;
            }

            if (isset($data['io']) && $server->io !== (int) $data['io']) {
                $server->io = $data['io'];
                $newBuild['io'] = (int) $server->io;
            }

            // Try save() here so if it fails we haven't contacted the daemon
            // This won't be committed unless the HTTP request succeedes anyways
            $server->save();

            if (! empty($newBuild)) {
                $server->node->guzzleClient([
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $server->node->daemonSecret,
                ])->request('PATCH', '/server', [
                    'json' => [
                        'build' => $newBuild,
                    ],
                ]);
            }

            DB::commit();

            return $server;
        } catch (TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('A TransferException occured while attempting to update the server configuration, check that the daemon is online. This error has been logged.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function updateStartup($id, array $data, $admin = false)
    {
        $server = Models\Server::with('variables', 'option.variables')->findOrFail($id);

        DB::transaction(function () use ($admin, $data, $server) {
            if (isset($data['startup']) && $admin) {
                $server->startup = $data['startup'];
                $server->save();
            }

            if ($server->option->variables) {
                foreach ($server->option->variables as &$variable) {
                    $set = isset($data['env_' . $variable->id]);

                    // If user is not an admin and are trying to edit a non-editable field
                    // or an invisible field just silently skip the variable.
                    if (! $admin && (! $variable->user_editable || ! $variable->user_viewable)) {
                        continue;
                    }

                    // Perform Field Validation
                    $validator = Validator::make([
                        'variable_value' => ($set) ? $data['env_' . $variable->id] : null,
                    ], [
                        'variable_value' => $variable->rules,
                    ]);

                    if ($validator->fails()) {
                        throw new DisplayValidationException(json_encode(
                            collect([
                                'notice' => ['There was a validation error with the `' . $variable->name . '` variable.'],
                            ])->merge($validator->errors()->toArray())
                        ));
                    }

                    $svar = Models\ServerVariable::firstOrNew([
                        'server_id' => $server->id,
                        'variable_id' => $variable->id,
                    ]);

                    // Set the value; if one was not passed set it to the default value
                    if ($set) {
                        $svar->variable_value = $data['env_' . $variable->id];

                    // Not passed, check if this record exists if so keep value, otherwise set default
                    } else {
                        $svar->variable_value = ($svar->exists) ? $svar->variable_value : $variable->default_value;
                    }

                    $svar->save();
                }
            }

            // Reload Variables
            $server->load('variables');
            $environment = $server->option->variables->map(function ($item, $key) use ($server) {
                $display = $server->variables->where('variable_id', $item->id)->pluck('variable_value')->first();

                return [
                    'variable' => $item->env_variable,
                    'value' => (! is_null($display)) ? $display : $item->default_value,
                ];
            });

            $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('PATCH', '/server', [
                'json' => [
                    'build' => [
                        'env|overwrite' => $environment->pluck('value', 'variable')->merge(['STARTUP' => $server->startup]),
                    ],
                ],
            ]);
        });
    }

    public function queueDeletion($id, $force = false)
    {
        $server = Models\Server::findOrFail($id);
        DB::beginTransaction();

        try {
            if ($force) {
                $server->installed = 3;
                $server->save();
            }

            $server->delete();

            return DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function delete($id, $force = false)
    {
        $server = Models\Server::withTrashed()->with('node')->findOrFail($id);

        // Handle server being restored previously or
        // an accidental queue.
        if (! $server->trashed()) {
            return;
        }

        DB::beginTransaction();
        try {
            // Unassign Allocations
            Models\Allocation::where('server_id', $server->id)->update([
                'server_id' => null,
            ]);

            // Remove Variables
            Models\ServerVariable::where('server_id', $server->id)->delete();

            // Remove SubUsers
            foreach (Models\Subuser::with('permissions')->where('server_id', $server->id)->get() as &$subuser) {
                foreach ($subuser->permissions as &$permission) {
                    $permission->delete();
                }
                $subuser->delete();
            }

            // Remove Downloads
            Models\Download::where('server', $server->uuid)->delete();

            // Clear Tasks
            Models\Task::where('server', $server->id)->delete();

            // Delete Databases
            // This is the one un-recoverable point where
            // transactions will not save us.
            //
            // @TODO: move to post-deletion event as a queued task!
            // $repository = new DatabaseRepository;
            // foreach (Models\Database::select('id')->where('server_id', $server->id)->get() as &$database) {
            //     $repository->drop($database->id);
            // }

            $server->node->guzzleClient([
                'X-Access-Token' => $server->node->daemonSecret,
                'X-Access-Server' => $server->uuid,
            ])->request('DELETE', '/servers');

            $server->forceDelete();
            DB::commit();
        } catch (TransferException $ex) {
            // Set installed is set to 3 when force deleting.
            if ($server->installed === 3 || $force) {
                $server->forceDelete();
                DB::commit();
            } else {
                DB::rollBack();
                throw $ex;
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function cancelDeletion($id)
    {
        $server = Models\Server::withTrashed()->findOrFail($id);
        $server->restore();

        $server->installed = 1;
        $server->save();
    }

    public function toggleInstall($id)
    {
        $server = Models\Server::findOrFail($id);
        if ($server->installed > 1) {
            throw new DisplayException('This server was marked as having a failed install or being deleted, you cannot override this.');
        }
        $server->installed = ! $server->installed;

        return $server->save();
    }

    /**
     * Suspends a server instance making it unable to be booted or used by a user.
     * @param  int $id
     * @return bool
     */
    public function suspend($id, $deleted = false)
    {
        $server = Models\Server::withTrashed()->with('node')->findOrFail($id);

        DB::beginTransaction();

        try {

            // Already suspended, no need to make more requests.
            if ($server->suspended) {
                return true;
            }

            $server->suspended = 1;
            $server->save();

            $server->node->guzzleClient([
                'X-Access-Token' => $server->node->daemonSecret,
                'X-Access-Server' => $server->uuid,
            ])->request('POST', '/server/suspend');

            return DB::commit();
        } catch (TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to contact the remote daemon to suspend this server.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Unsuspends a server instance.
     * @param  int $id
     * @return bool
     */
    public function unsuspend($id)
    {
        $server = Models\Server::with('node')->findOrFail($id);

        DB::beginTransaction();

        try {

            // Already unsuspended, no need to make more requests.
            if ($server->suspended === 0) {
                return true;
            }

            $server->suspended = 0;
            $server->save();

            $server->node->guzzleClient([
                'X-Access-Token' => $server->node->daemonSecret,
                'X-Access-Server' => $server->uuid,
            ])->request('POST', '/server/unsuspend');

            return DB::commit();
        } catch (TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to contact the remote daemon to un-suspend this server.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function updateSFTPPassword($id, $password)
    {
        $server = Models\Server::with('node')->findOrFail($id);

        $validator = Validator::make(['password' => $password], [
            'password' => 'required|regex:/^((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})$/',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        DB::beginTransaction();
        $server->sftp_password = Crypt::encrypt($password);

        try {
            $server->save();

            $server->node->guzzleClient([
                'X-Access-Token' => $server->node->daemonSecret,
                'X-Access-Server' => $server->uuid,
            ])->request('POST', '/server/password', [
                'json' => ['password' => $password],
            ]);

            DB::commit();

            return true;
        } catch (TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error while attmping to contact the remote service to change the password.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
