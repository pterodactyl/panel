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
namespace Pterodactyl\Repositories;

use Crypt;
use DB;
use Debugbar;
use Validator;
use Log;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;
use Pterodactyl\Services\DeploymentService;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServerRepository
{

    protected $daemonPermissions = [
        's:*'
    ];

    public function __construct()
    {
        //
    }

    /**
     * Generates a SFTP username for a server given a server name.
     *
     * @param  string $name
     * @return string
     */
    protected function generateSFTPUsername($name)
    {

        $name = preg_replace('/\s+/', '', $name);
        if (strlen($name) > 6) {
            return strtolower('ptdl-' . substr($name, 0, 6) . '_' . str_random(5));
        }

        return strtolower('ptdl-' . $name . '_' . str_random((11 - strlen($name))));

    }

    /**
     * Adds a new server to the system.
     * @param   array  $data  An array of data descriptors for creating the server. These should align to the columns in the database.
     * @return  integer
     */
    public function create(array $data)
    {

        // Validate Fields
        $validator = Validator::make($data, [
            'owner' => 'bail|required|string',
            'name' => 'required|regex:/^([\w -]{4,35})$/',
            'memory' => 'required|numeric|min:0',
            'swap' => 'required|numeric|min:-1',
            'io' => 'required|numeric|min:10|max:1000',
            'cpu' => 'required|numeric|min:0',
            'disk' => 'required|numeric|min:0',
            'service' => 'bail|required|numeric|min:1|exists:services,id',
            'option' => 'bail|required|numeric|min:1|exists:service_options,id',
            'startup' => 'string',
            'custom_image_name' => 'required_if:use_custom_image,on',
            'auto_deploy' => 'sometimes|boolean'
        ]);

        $validator->sometimes('node', 'bail|required|numeric|min:1|exists:nodes,id', function ($input) {
            return !($input->auto_deploy);
        });

        $validator->sometimes('ip', 'required|ip', function ($input) {
            return (!$input->auto_deploy && !$input->allocation);
        });

        $validator->sometimes('port', 'required|numeric|min:1|max:65535', function ($input) {
            return (!$input->auto_deploy && !$input->allocation);
        });

        $validator->sometimes('allocation', 'numeric|exists:allocations,id', function ($input) {
            return !($input->auto_deploy || ($input->port && $input->ip));
        });


        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if (is_int($data['owner'])) {
            $user = Models\User::select('id')->where('id', $data['owner'])->first();
        } else {
            $user = Models\User::select('id')->where('email', $data['owner'])->first();
        }

        if (!$user) {
            throw new DisplayValidationException('The user id or email passed to the function was not found on the system.');
        }

        $autoDeployed = false;
        if (isset($data['auto_deploy']) && in_array($data['auto_deploy'], [true, 1, "1"])) {
            // This is an auto-deployment situation
            // Ignore any other passed node data
            unset($data['node'], $data['ip'], $data['port'], $data['allocation']);

            $autoDeployed = true;
            $node = DeploymentService::smartRandomNode($data['memory'], $data['disk'], $data['location']);
            $allocation = DeploymentService::randomAllocation($node->id);
        } else {
            $node = Models\Node::getByID($data['node']);
        }

        // Verify IP & Port are a.) free and b.) assigned to the node.
        // We know the node exists because of 'exists:nodes,id' in the validation
        if (!$autoDeployed) {
            if (!isset($data['allocation'])) {
                $allocation = Models\Allocation::where('ip', $data['ip'])->where('port', $data['port'])->where('node', $data['node'])->whereNull('assigned_to')->first();
            } else {
                $allocation = Models\Allocation::where('id' , $data['allocation'])->where('node', $data['node'])->whereNull('assigned_to')->first();
            }
        }

        // Something failed in the query, either that combo doesn't exist, or it is in use.
        if (!$allocation) {
            throw new DisplayException('The selected IP/Port combination or Allocation ID is either already in use, or unavaliable for this node.');
        }

        // Validate those Service Option Variables
        // We know the service and option exists because of the validation.
        // We need to verify that the option exists for the service, and then check for
        // any required variable fields. (fields are labeled env_<env_variable>)
        $option = Models\ServiceOptions::where('id', $data['option'])->where('parent_service', $data['service'])->first();
        if (!$option) {
            throw new DisplayException('The requested service option does not exist for the specified service.');
        }

        // Load up the Service Information
        $service = Models\Service::find($option->parent_service);

        // Check those Variables
        $variables = Models\ServiceVariables::where('option_id', $data['option'])->get();
        $variableList = [];
        if ($variables) {
            foreach($variables as $variable) {

                // Is the variable required?
                if (!$data['env_' . $variable->env_variable]) {
                    if ($variable->required === 1) {
                        throw new DisplayException('A required service option variable field (env_' . $variable->env_variable . ') was missing from the request.');
                    }
                    $variableList = array_merge($variableList, [[
                        'id' => $variable->id,
                        'env' => $variable->env_variable,
                        'val' => $variable->default_value
                    ]]);
                    continue;
                }

                // Check aganist Regex Pattern
                if (!is_null($variable->regex) && !preg_match($variable->regex, $data['env_' . $variable->env_variable])) {
                    throw new DisplayException('Failed to validate service option variable field (env_' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                }

                $variableList = array_merge($variableList, [[
                    'id' => $variable->id,
                    'env' => $variable->env_variable,
                    'val' => $data['env_' . $variable->env_variable]
                ]]);
                continue;
            }
        }

        // Check Overallocation
        if (!$autoDeployed) {
            if (is_numeric($node->memory_overallocate) || is_numeric($node->disk_overallocate)) {

                $totals = Models\Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node', $node->id)->first();

                // Check memory limits
                if (is_numeric($node->memory_overallocate)) {
                    $newMemory = $totals->memory + $data['memory'];
                    $memoryLimit = ($node->memory * (1 + ($node->memory_overallocate / 100)));
                    if($newMemory > $memoryLimit) {
                        throw new DisplayException('The amount of memory allocated to this server would put the node over its allocation limits. This node is allowed ' . ($node->memory_overallocate + 100) . '% of its assigned ' . $node->memory . 'Mb of memory (' . $memoryLimit . 'Mb) of which ' . (($totals->memory / $node->memory) * 100) . '% (' . $totals->memory . 'Mb) is in use already. By allocating this server the node would be at ' . (($newMemory / $node->memory) * 100) . '% (' . $newMemory . 'Mb) usage.');
                    }
                }

                // Check Disk Limits
                if (is_numeric($node->disk_overallocate)) {
                    $newDisk = $totals->disk + $data['disk'];
                    $diskLimit = ($node->disk * (1 + ($node->disk_overallocate / 100)));
                    if($newDisk > $diskLimit) {
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
            $generatedUuid = $uuid->generate('servers', 'uuid');
            $server->fill([
                'uuid' => $generatedUuid,
                'uuidShort' => $uuid->generateShort('servers', 'uuidShort', $generatedUuid),
                'node' => $node->id,
                'name' => $data['name'],
                'suspended' => 0,
                'owner' => $user->id,
                'memory' => $data['memory'],
                'swap' => $data['swap'],
                'disk' => $data['disk'],
                'io' => $data['io'],
                'cpu' => $data['cpu'],
                'oom_disabled' => (isset($data['oom_disabled'])) ? true : false,
                'allocation' => $allocation->id,
                'service' => $data['service'],
                'option' => $data['option'],
                'startup' => $data['startup'],
                'daemonSecret' => $uuid->generate('servers', 'daemonSecret'),
                'image' => (isset($data['custom_image_name'])) ? $data['custom_image_name'] : $option->docker_image,
                'username' => $this->generateSFTPUsername($data['name']),
                'sftp_password' => Crypt::encrypt('not set')
            ]);
            $server->save();

            // Mark Allocation in Use
            $allocation->assigned_to = $server->id;
            $allocation->save();

            // Add Variables
            $environmentVariables = [];
            $environmentVariables = array_merge($environmentVariables, [
                'STARTUP' => $data['startup']
            ]);
            foreach($variableList as $item) {
                $environmentVariables = array_merge($environmentVariables, [
                    $item['env'] => $item['val']
                ]);
                Models\ServerVariables::create([
                    'server_id' => $server->id,
                    'variable_id' => $item['id'],
                    'variable_value' => $item['val']
                ]);
            }

            $client = Models\Node::guzzleRequest($node->id);
            $client->request('POST', '/servers', [
                'headers' => [
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'uuid' => (string) $server->uuid,
                    'user' => $server->username,
                    'build' => [
                        'default' => [
                            'ip' => $allocation->ip,
                            'port' => (int) $allocation->port
                        ],
                        'ports' => [
                            (string) $allocation->ip => [ (int) $allocation->port ]
                        ],
                        'env' => $environmentVariables,
                        'memory' => (int) $server->memory,
                        'swap' => (int) $server->swap,
                        'io' => (int) $server->io,
                        'cpu' => (int) $server->cpu,
                        'disk' => (int) $server->disk,
                        'image' => (isset($data['custom_image_name'])) ? $data['custom_image_name'] : $option->docker_image
                    ],
                    'service' => [
                        'type' => $service->file,
                        'option' => $option->tag
                    ],
                    'keys' => [
                        (string) $server->daemonSecret => $this->daemonPermissions
                    ],
                    'rebuild' => false
                ]
            ]);

            DB::commit();
            return $server->id;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error while attempting to connect to the daemon to add this server.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

    }

    /**
     * [updateDetails description]
     * @param  integer  $id
     * @param  array    $data
     * @return boolean
     */
    public function updateDetails($id, array $data)
    {

        $uuid = new UuidService;
        $resetDaemonKey = false;

        // Validate Fields
        $validator = Validator::make($data, [
            'owner' => 'email|exists:users,email',
            'name' => 'regex:([\w -]{4,35})'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();

        try {
            $server = Models\Server::findOrFail($id);
            $owner = Models\User::findOrFail($server->owner);

            // Update daemon secret if it was passed.
            if ((isset($data['reset_token']) && $data['reset_token'] === true) || (isset($data['owner']) && $data['owner'] !== $owner->email)) {
                $oldDaemonKey = $server->daemonSecret;
                $server->daemonSecret = $uuid->generate('servers', 'daemonSecret');
                $resetDaemonKey = true;
            }

            // Update Server Owner if it was passed.
            if (isset($data['owner']) && $data['owner'] !== $owner->email) {
                $newOwner = Models\User::select('id')->where('email', $data['owner'])->first();
                $server->owner = $newOwner->id;
            }

            // Update Server Name if it was passed.
            if (isset($data['name'])) {
                $server->name = $data['name'];
            }

            // Save our changes
            $server->save();

            // Do we need to update? If not, return successful.
            if (!$resetDaemonKey) {
                DB::commit();
                return true;
            }

            // If we need to update do it here.
            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $res = $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'exceptions' => false,
                'json' => [
                    'keys' => [
                        (string) $oldDaemonKey => [],
                        (string) $server->daemonSecret => $this->daemonPermissions
                    ]
                ]
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
     * [updateContainer description]
     * @param  int      $id
     * @param  array    $data
     * @return bool
     */
    public function updateContainer($id, array $data)
    {
        $validator = Validator::make($data, [
            'image' => 'required|string'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();
        try {
            $server = Models\Server::findOrFail($id);

            $server->image = $data['image'];
            $server->save();

            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'build' => [
                        'image' => $server->image
                    ]
                ]
            ]);

            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to update the container image.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

    }

    /**
     * [changeBuild description]
     * @param  integer  $id
     * @param  array    $data
     * @return boolean
     */
    public function changeBuild($id, array $data)
    {

        $validator = Validator::make($data, [
            'default' => [
                'string',
                'regex:/^(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5]))\.(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5]))\.(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5]))\.(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5])):(\d{1,5})$/'
            ],
            'add_additional' => 'nullable|array',
            'remove_additional' => 'nullable|array',
            'memory' => 'integer|min:0',
            'swap' => 'integer|min:-1',
            'io' => 'integer|min:10|max:1000',
            'cpu' => 'integer|min:0',
            'disk' => 'integer|min:0'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();

        try {
            $server = Models\Server::findOrFail($id);
            $allocation = Models\Allocation::findOrFail($server->allocation);

            $newBuild = [];

            if (isset($data['default'])) {
                list($ip, $port) = explode(':', $data['default']);
                if ($ip !== $allocation->ip || (int) $port !== $allocation->port) {
                    $selection = Models\Allocation::where('ip', $ip)->where('port', $port)->where('assigned_to', $server->id)->first();
                    if (!$selection) {
                        throw new DisplayException('The requested default connection (' . $ip . ':' . $port . ') is not allocated to this server.');
                    }

                    $server->allocation = $selection->id;
                    $newBuild['default'] = [
                        'ip' => $ip,
                        'port' => (int) $port
                    ];

                    // Re-Run to keep updated for rest of function
                    $allocation = Models\Allocation::findOrFail($server->allocation);
                }
            }

            $newPorts = false;
            // Remove Assignments
            if (isset($data['remove_additional'])) {
                $newPorts = true;
                foreach ($data['remove_additional'] as $id => $combo) {
                    list($ip, $port) = explode(':', $combo);
                    // Invalid, not worth killing the whole thing, we'll just skip over it.
                    if (!filter_var($ip, FILTER_VALIDATE_IP) || !preg_match('/^(\d{1,5})$/', $port)) {
                        continue;
                    }

                    // Can't remove the assigned IP/Port combo
                    if ($ip === $allocation->ip && $port === $allocation->port) {
                        continue;
                    }

                    Models\Allocation::where('ip', $ip)->where('port', $port)->where('assigned_to', $server->id)->update([
                        'assigned_to' => null
                    ]);
                }
            }

            // Add Assignments
            if (isset($data['add_additional'])) {
                $newPorts = true;
                foreach ($data['add_additional'] as $id => $combo) {
                    list($ip, $port) = explode(':', $combo);
                    // Invalid, not worth killing the whole thing, we'll just skip over it.
                    if (!filter_var($ip, FILTER_VALIDATE_IP) || !preg_match('/^(\d{1,5})$/', $port)) {
                        continue;
                    }

                    // Don't allow double port assignments
                    if (Models\Allocation::where('port', $port)->where('assigned_to', $server->id)->count() !== 0) {
                        continue;
                    }

                    Models\Allocation::where('ip', $ip)->where('port', $port)->whereNull('assigned_to')->update([
                        'assigned_to' => $server->id
                    ]);
                }
            }

            // Loop All Assignments
            $additionalAssignments = [];
            $assignments = Models\Allocation::where('assigned_to', $server->id)->get();
            foreach ($assignments as &$assignment) {
                if (array_key_exists((string) $assignment->ip, $additionalAssignments)) {
                    array_push($additionalAssignments[ (string) $assignment->ip ], (int) $assignment->port);
                } else {
                    $additionalAssignments[ (string) $assignment->ip ] = [ (int) $assignment->port ];
                }
            }

            if ($newPorts === true) {
                $newBuild['ports|overwrite'] = $additionalAssignments;
            }

            // @TODO: verify that server can be set to this much memory without
            // going over node limits.
            if (isset($data['memory'])) {
                $server->memory = $data['memory'];
            }

            if (isset($data['swap'])) {
                $server->swap = $data['swap'];
            }

            // @TODO: verify that server can be set to this much disk without
            // going over node limits.
            if (isset($data['disk'])) {
                $server->disk = $data['disk'];
            }

            if (isset($data['cpu'])) {
                $server->cpu = $data['cpu'];
            }

            if (isset($data['io'])) {
                $server->io = $data['io'];
            }

            // Try save() here so if it fails we haven't contacted the daemon
            // This won't be committed unless the HTTP request succeedes anyways
            $server->save();

            $newBuild['memory'] = (int) $server->memory;
            $newBuild['swap'] = (int) $server->swap;
            $newBuild['io'] = (int) $server->io;
            $newBuild['cpu'] = (int) $server->cpu;
            $newBuild['disk'] = (int) $server->disk;

            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'build' => $newBuild
                ]
            ]);

            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to update the configuration.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

    }

    public function updateStartup($id, array $data, $admin = false)
    {

        $server = Models\Server::findOrFail($id);

        DB::beginTransaction();

        try {
            // Check the startup
            if (isset($data['startup'])) {
                $server->startup = $data['startup'];
                $server->save();
            }

            // Check those Variables
            $variables = Models\ServiceVariables::select(
                    'service_variables.*',
                    DB::raw('COALESCE(server_variables.variable_value, service_variables.default_value) as a_currentValue')
                )->leftJoin('server_variables', 'server_variables.variable_id', '=', 'service_variables.id')
                ->where('option_id', $server->option)
                ->get();

            $variableList = [];
            if ($variables) {
                foreach($variables as &$variable) {
                    // Move on if the new data wasn't even sent
                    if (!isset($data[$variable->env_variable])) {
                        $variableList = array_merge($variableList, [[
                            'id' => $variable->id,
                            'env' => $variable->env_variable,
                            'val' => $variable->a_currentValue
                        ]]);
                        continue;
                    }

                    // Update Empty but skip validation
                    if (empty($data[$variable->env_variable])) {
                        $variableList = array_merge($variableList, [[
                            'id' => $variable->id,
                            'env' => $variable->env_variable,
                            'val' => null
                        ]]);
                        continue;
                    }

                    // Is the variable required?
                    // @TODO: is this even logical to perform this check?
                    if (isset($data[$variable->env_variable]) && empty($data[$variable->env_variable])) {
                        if ($variable->required === 1) {
                            throw new DisplayException('A required service option variable field (' . $variable->env_variable . ') was included in this request but was left blank.');
                        }
                    }

                    // Variable hidden and/or not user editable
                    if (($variable->user_viewable === 0 || $variable->user_editable === 0) && !$admin) {
                        throw new DisplayException('A service option variable field (' . $variable->env_variable . ') does not exist or you do not have permission to edit it.');
                    }

                    // Check aganist Regex Pattern
                    if (!is_null($variable->regex) && !preg_match($variable->regex, $data[$variable->env_variable])) {
                        throw new DisplayException('Failed to validate service option variable field (' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                    }

                    $variableList = array_merge($variableList, [[
                        'id' => $variable->id,
                        'env' => $variable->env_variable,
                        'val' => $data[$variable->env_variable]
                    ]]);
                }
            }

            // Add Variables
            $environmentVariables = [];
            $environmentVariables = array_merge($environmentVariables, [
                'STARTUP' => $server->startup
            ]);
            foreach($variableList as $item) {
                $environmentVariables = array_merge($environmentVariables, [
                    $item['env'] => $item['val']
                ]);

                // Update model or make a new record if it doesn't exist.
                $model = Models\ServerVariables::firstOrNew([
                    'variable_id' => $item['id'],
                    'server_id' => $server->id
                ]);
                $model->variable_value = $item['val'];
                $model->save();
            }

            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'build' => [
                        'env|overwrite' => $environmentVariables
                    ]
                ]
            ]);

            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to update the server configuration.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

    }

    public function deleteServer($id, $force)
    {
        $server = Models\Server::findOrFail($id);
        $node = Models\Node::findOrFail($server->node);
        DB::beginTransaction();

        try {
            // Delete Allocations
            Models\Allocation::where('assigned_to', $server->id)->update([
                'assigned_to' => null
            ]);

            // Remove Variables
            Models\ServerVariables::where('server_id', $server->id)->delete();

            // Remove SubUsers
            Models\Subuser::where('server_id', $server->id)->delete();

            // Remove Permissions
            Models\Permission::where('server_id', $server->id)->delete();

            // Remove Downloads
            Models\Download::where('server', $server->uuid)->delete();

            $client = Models\Node::guzzleRequest($server->node);
            $client->request('DELETE', '/servers', [
                'headers' => [
                    'X-Access-Token' => $node->daemonSecret,
                    'X-Access-Server' => $server->uuid
                ]
            ]);

            $server->delete();
            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            if ($force === 'force') {
                $server->delete();
                DB::commit();
                return true;
            } else {
                DB::rollBack();
                throw new DisplayException('An error occured while attempting to delete the server on the daemon.', $ex);
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function toggleInstall($id)
    {
        $server = Models\Server::findOrFail($id);
        if ($server->installed === 2) {
            throw new DisplayException('This server was marked as having a failed install, you cannot override this.');
        }
        $server->installed = ($server->installed === 1) ? 0 : 1;
        return $server->save();
    }

    /**
     * Suspends a server instance making it unable to be booted or used by a user.
     * @param  integer $id
     * @return boolean
     */
    public function suspend($id)
    {
        $server = Models\Server::findOrFail($id);
        $node = Models\Node::findOrFail($server->node);

        DB::beginTransaction();

        try {

            // Already suspended, no need to make more requests.
            if ($server->suspended === 1) {
                return true;
            }

            $server->suspended = 1;
            $server->save();

            $client = Models\Node::guzzleRequest($server->node);
            $client->request('POST', '/server/suspend', [
                'headers' => [
                    'X-Access-Token' => $node->daemonSecret,
                    'X-Access-Server' => $server->uuid
                ]
            ]);

            return DB::commit();
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to contact the remote daemon to suspend this server.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Unsuspends a server instance.
     * @param  integer $id
     * @return boolean
     */
    public function unsuspend($id)
    {
        $server = Models\Server::findOrFail($id);
        $node = Models\Node::findOrFail($server->node);

        DB::beginTransaction();

        try {

            // Already unsuspended, no need to make more requests.
            if ($server->suspended === 0) {
                return true;
            }

            $server->suspended = 0;
            $server->save();

            $client = Models\Node::guzzleRequest($server->node);
            $client->request('POST', '/server/unsuspend', [
                'headers' => [
                    'X-Access-Token' => $node->daemonSecret,
                    'X-Access-Server' => $server->uuid
                ]
            ]);

            return DB::commit();
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to contact the remote daemon to un-suspend this server.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function updateSFTPPassword($id, $password)
    {
        $server = Models\Server::findOrFail($id);
        $node = Models\Node::findOrFail($server->node);

        $validator = Validator::make([
            'password' => $password,
        ], [
            'password' => 'required|regex:/^((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})$/'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        DB::beginTransaction();
        $server->sftp_password = Crypt::encrypt($password);

        try {
            $server->save();

            $client = Models\Node::guzzleRequest($server->node);
            $client->request('POST', '/server/password', [
                'headers' => [
                    'X-Access-Token' => $node->daemonSecret,
                    'X-Access-Server' => $server->uuid
                ],
                'json' => [
                    'password' => $password,
                ],
            ]);

            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error while attmping to contact the remote service to change the password.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

    }

}
