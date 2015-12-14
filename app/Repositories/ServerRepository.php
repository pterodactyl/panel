<?php

namespace Pterodactyl\Repositories;

use DB;
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
            'disk' => 'required|numeric|min:1',
            'cpu' => 'required|numeric|min:0',
            'io' => 'required|numeric|min:10|max:1000',
            'ip' => 'required|ip',
            'port' => 'required|numeric|min:1|max:65535',
            'service' => 'required|numeric|min:1|exists:services,id',
            'option' => 'required|numeric|min:1|exists:service_options,id',
            'custom_image_name' => 'required_if:use_custom_image,on',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()->all()));
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
        if ($variables) {
            foreach($variables as $variable) {

                // Is the variable required?
                if (!$data['env_' . $variable->env_variable]) {
                    if ($variable->required === 1) {
                        throw new DisplayException('A required service option variable field (env_' . $variable->env_variable . ') was missing from the request.');
                    }

                    $data['env_' . $variable->env_variable] = $variable->default_value;
                    continue;
                }

                // Check aganist Regex Pattern
                if (!is_null($variable->regex) && !preg_match($variable->regex, $data['env_' . $variable->env_variable])) {
                    throw new DisplayException('Failed to validate service option variable field (env_' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                }

                continue;

            }
        }

        return (new UuidService)->generateShort();
        //return $this->generateSFTPUsername($data['name']);

    }

}
