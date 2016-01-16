<?php

namespace Pterodactyl\Repositories;

use DB;
use Validator;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;

use IPTools\Network;
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
            'daemonListen' => 'required|numeric|between:1,65535',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        // Verify the FQDN
        if (filter_var($data['fqdn'], FILTER_VALIDATE_IP)) {
            throw new DisplayException('The FQDN provided was an IP address. You must use a FQDN.');
        }
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

    public function update($id, array $data)
    {
        // Validate Fields
        $validator = $validator = Validator::make($data, [
            'name' => 'regex:/^([\w .-]{1,100})$/',
            'location' => 'numeric|min:1|exists:locations,id',
            'public' => 'numeric|between:0,1',
            'fqdn' => 'string|unique:nodes,fqdn,' . $id,
            'scheme' => 'regex:/^(http(s)?)$/',
            'memory' => 'numeric|min:1',
            'memory_overallocate' => 'numeric|min:-1',
            'disk' => 'numeric|min:1',
            'disk_overallocate' => 'numeric|min:-1',
            'daemonBase' => 'regex:/^([\/][\d\w.\-\/]+)$/',
            'daemonSFTP' => 'numeric|between:1,65535',
            'daemonListen' => 'numeric|between:1,65535',
            'reset_secret' => 'sometimes|accepted',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        // Verify the FQDN
        if (isset($data['fqdn'])) {
            if (filter_var($data['fqdn'], FILTER_VALIDATE_IP)) {
                throw new DisplayException('The FQDN provided was an IP address. You must use a FQDN.');
            }
            if (!filter_var(gethostbyname($data['fqdn']), FILTER_VALIDATE_IP)) {
                throw new DisplayException('The FQDN provided does not resolve to a valid IP address.');
            }
        }

        // Should we be nulling the overallocations?
        if (isset($data['memory_overallocate'])) {
            $data['memory_overallocate'] = ($data['memory_overallocate'] < 0) ? null : $data['memory_overallocate'];
        }

        if (isset($data['disk_overallocate'])) {
            $data['disk_overallocate'] = ($data['disk_overallocate'] < 0) ? null : $data['disk_overallocate'];
        }

        // Set the Secret
        if (isset($data['reset_secret'])) {
            $uuid = new UuidService;
            $data['daemonSecret'] = (string) $uuid->generate('nodes', 'daemonSecret');
            unset($data['reset_secret']);
        }

        // Store the Data
        $node = Models\Node::findOrFail($id);
        return $node->update($data);

    }

    public function addAllocations($id, array $allocations)
    {
        $node = Models\Node::findOrFail($id);

        DB::beginTransaction();
        foreach($allocations as $rawIP => $ports) {
            $parsedIP = Network::parse($rawIP);
            foreach($parsedIP as $ip) {
                foreach($ports as $port) {
                    if (!is_int($port) && !preg_match('/^(\d{1,5})-(\d{1,5})$/', $port)) {
                        throw new DisplayException('The mapping for ' . $port . ' is invalid and cannot be processed.');
                    }
                    if (preg_match('/^(\d{1,5})-(\d{1,5})$/', $port, $matches)) {
                        foreach(range($matches[1], $matches[2]) as $assignPort) {
                            $alloc = Models\Allocation::firstOrNew([
                                'node' => $node->id,
                                'ip' => $ip,
                                'port' => $assignPort
                            ]);
                            if (!$alloc->exists) {
                                $alloc->fill([
                                    'node' => $node->id,
                                    'ip' => $ip,
                                    'port' => $assignPort,
                                    'assigned_to' => null
                                ]);
                                $alloc->save();
                            }
                        }
                    } else {
                        $alloc = Models\Allocation::firstOrNew([
                            'node' => $node->id,
                            'ip' => $ip,
                            'port' => $port
                        ]);
                        if (!$alloc->exists) {
                            $alloc->fill([
                                'node' => $node->id,
                                'ip' => $ip,
                                'port' => $port,
                                'assigned_to' => null
                            ]);
                            $alloc->save();
                        }
                    }
                }
            }
        }

        try {
            DB::commit();
            return true;
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function delete($id)
    {
        // @TODO: add logic;
        return true;
    }

}
