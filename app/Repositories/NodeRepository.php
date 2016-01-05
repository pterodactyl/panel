<?php

namespace Pterodactyl\Repositories;

use Validator;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class NodeRepository {

    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        // Validate Fields
        $validator = Validator::make($data, [
            'name' => 'required|regex:/^([\w .-]{1,100})$/',
            'location' => 'required|numeric|min:1|exists:locations,id',
            'public' => 'required|numeric|between:0,1',
            'fqdn' => 'required|string|unique:nodes,fqdn',
            'scheme' => 'required|regex:/^(http(s)?)$/',
            'memory' => 'required|numeric|min:1',
            'memory_overallocate' => 'required|numeric|min:-1',
            'disk' => 'required|numeric|min:1',
            'disk_overallocate' => 'required|numeric|min:-1',
            'daemonBase' => 'required|regex:/^([\/][\d\w.\-\/]+)$/',
            'daemonSFTP' => 'required|numeric|between:1,65535',
            'daemonListen' => 'required|numeric|between:1,65535'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        // Verify the FQDN
        if (!filter_var(gethostbyname($data['fqdn']), FILTER_VALIDATE_IP)) {
            throw new DisplayException('The FQDN provided does not resolve to a valid IP address.');
        }

        // Should we be nulling the overallocations?
        $data['memory_overallocate'] = ($data['memory_overallocate'] < 0) ? null : $data['memory_overallocate'];
        $data['disk_overallocate'] = ($data['disk_overallocate'] < 0) ? null : $data['disk_overallocate'];

        // Set the Secret
        $uuid = new UuidService;
        $data['daemonSecret'] = (string) $uuid->generate('nodes', 'daemonSecret');

        // Store the Data
        $node = new Models\Node;
        $node->fill($data);
        $node->save();

        return $node->id;

    }

}
