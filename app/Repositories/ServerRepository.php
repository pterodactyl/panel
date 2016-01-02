<?php

namespace Pterodactyl\Repositories;

use DB;
use Debugbar;
use Validator;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServerRepository
{

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
     * @param array  $data  An array of data descriptors for creating the server. These should align to the columns in the database.
     */
    public function create(array $data)
    {

        // Validate Fields
        $validator = Validator::make($data, [
            'owner' => 'required|email|exists:users,email',
            'node' => 'required|numeric|min:1|exists:nodes,id',
            'name' => 'required|regex:([\w -]{4,35})',
            'memory' => 'required|numeric|min:1',
            'swap' => 'required|numeric|min:0',
            'disk' => 'required|numeric|min:1',
            'cpu' => 'required|numeric|min:0',
            'io' => 'required|numeric|min:10|max:1000',
            'ip' => 'required|ip',
            'port' => 'required|numeric|min:1|max:65535',
            'service' => 'required|numeric|min:1|exists:services,id',
            'option' => 'required|numeric|min:1|exists:service_options,id',
            'startup' => 'required',
            'custom_image_name' => 'required_if:use_custom_image,on',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        // Get the User ID; user exists since we passed the 'exists:users,email' part of the validation
        $user = Models\User::select('id')->where('email', $data['owner'])->first();

        // Verify IP & Port are a.) free and b.) assigned to the node.
        // We know the node exists because of 'exists:nodes,id' in the validation
        $node = Models\Node::find($data['node']);
        $allocation = Models\Allocation::where('ip', $data['ip'])->where('port', $data['port'])->where('node', $data['node'])->whereNull('assigned_to')->first();

        // Something failed in the query, either that combo doesn't exist, or it is in use.
        if (!$allocation) {
            throw new DisplayException('The selected IP/Port combination (' . $data['ip'] . ':' . $data['port'] . ') is either already in use, or unavaliable for this node.');
        }

        // Validate those Service Option Variables
        // We know the service and option exists because of the validation.
        // We need to verify that the option exists for the service, and then check for
        // any required variable fields. (fields are labeled env_<env_variable>)
        $option = Models\ServiceOptions::where('id', $data['option'])->where('parent_service', $data['service'])->first();
        if (!$option) {
            throw new DisplayException('The requested service option does not exist for the specified service.');
        }

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
                        'var_id' => $variable->id,
                        'var_val' => $variable->default_value
                    ]]);

                    continue;
                }

                // Check aganist Regex Pattern
                if (!is_null($variable->regex) && !preg_match($variable->regex, $data['env_' . $variable->env_variable])) {
                    throw new DisplayException('Failed to validate service option variable field (env_' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                }

                $variableList = array_merge($variableList, [[
                    'var_id' => $variable->id,
                    'var_val' => $data['env_' . $variable->env_variable]
                ]]);

                continue;
            }
        }

        // Check Overallocation
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

        DB::beginTransaction();

        $uuid = new UuidService;

        // Add Server to the Database
        $server = new Models\Server;
        $generatedUuid = $uuid->generate('servers', 'uuid');
        $server->fill([
            'uuid' => $generatedUuid,
            'uuidShort' => $uuid->generateShort('servers', 'uuidShort', $generatedUuid),
            'node' => $data['node'],
            'name' => $data['name'],
            'active' => 1,
            'owner' => $user->id,
            'memory' => $data['memory'],
            'swap' => $data['swap'],
            'disk' => $data['disk'],
            'io' => $data['io'],
            'cpu' => $data['cpu'],
            'oom_disabled' => (isset($data['oom_disabled'])) ? true : false,
            'ip' => $data['ip'],
            'port' => $data['port'],
            'service' => $data['service'],
            'option' => $data['option'],
            'startup' => $data['startup'],
            'daemonSecret' => $uuid->generate('servers', 'daemonSecret'),
            'username' => $this->generateSFTPUsername($data['name'])
        ]);
        $server->save();

        // Mark Allocation in Use
        $allocation->assigned_to = $server->id;
        $allocation->save();

        // Add Variables
        foreach($variableList as $item) {
            Models\ServerVariables::create([
                'server_id' => $server->id,
                'variable_id' => $item['var_id'],
                'variable_value' => $item['var_val']
            ]);
        }

        try {

            // Add logic for communicating with Wings to make the server in here.
            // We should add the server regardless of the Wings response, but
            // handle the error and then allow the server to be re-deployed.

            DB::commit();
            return $server->id;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

}
