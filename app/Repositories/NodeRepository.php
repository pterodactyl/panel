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
use Validator;
use IPTools\Network;
use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class NodeRepository
{
    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        // Validate Fields
        $validator = Validator::make($data, [
            'name' => 'required|regex:/^([\w .-]{1,100})$/',
            'location_id' => 'required|numeric|min:1|exists:locations,id',
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

        // Verify the FQDN if using SSL
        if (filter_var($data['fqdn'], FILTER_VALIDATE_IP) && $data['scheme'] === 'https') {
            throw new DisplayException('A fully qualified domain name is required to use a secure comunication method on this node.');
        }

        // Verify FQDN is resolvable, or if not using SSL that the IP is valid.
        if (! filter_var(gethostbyname($data['fqdn']), FILTER_VALIDATE_IP)) {
            throw new DisplayException('The FQDN (or IP Address) provided does not resolve to a valid IP address.');
        }

        // Should we be nulling the overallocations?
        $data['memory_overallocate'] = ($data['memory_overallocate'] < 0) ? null : $data['memory_overallocate'];
        $data['disk_overallocate'] = ($data['disk_overallocate'] < 0) ? null : $data['disk_overallocate'];

        // Set the Secret
        $uuid = new UuidService;
        $data['daemonSecret'] = (string) $uuid->generate('nodes', 'daemonSecret');

        return Models\Node::create($data);
    }

    public function update($id, array $data)
    {
        $node = Models\Node::findOrFail($id);

        // Validate Fields
        $validator = $validator = Validator::make($data, [
            'name' => 'regex:/^([\w .-]{1,100})$/',
            'location_id' => 'numeric|min:1|exists:locations,id',
            'public' => 'numeric|between:0,1',
            'fqdn' => 'string|unique:nodes,fqdn,' . $id,
            'scheme' => 'regex:/^(http(s)?)$/',
            'memory' => 'numeric|min:1',
            'memory_overallocate' => 'numeric|min:-1',
            'disk' => 'numeric|min:1',
            'disk_overallocate' => 'numeric|min:-1',
            'upload_size' => 'numeric|min:0',
            'daemonBase' => 'sometimes|regex:/^([\/][\d\w.\-\/]+)$/',
            'daemonSFTP' => 'numeric|between:1,65535',
            'daemonListen' => 'numeric|between:1,65535',
            'reset_secret' => 'sometimes|nullable|accepted',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        // Verify the FQDN
        if (isset($data['fqdn'])) {

            // Verify the FQDN if using SSL
            if ((isset($data['scheme']) && $data['scheme'] === 'https') || (! isset($data['scheme']) && $node->scheme === 'https')) {
                if (filter_var($data['fqdn'], FILTER_VALIDATE_IP)) {
                    throw new DisplayException('A fully qualified domain name is required to use secure comunication on this node.');
                }
            }

            // Verify FQDN is resolvable, or if not using SSL that the IP is valid.
            if (! filter_var(gethostbyname($data['fqdn']), FILTER_VALIDATE_IP)) {
                throw new DisplayException('The FQDN (or IP Address) provided does not resolve to a valid IP address.');
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
        if (isset($data['reset_secret']) && ! is_null($data['reset_secret'])) {
            $uuid = new UuidService;
            $data['daemonSecret'] = (string) $uuid->generate('nodes', 'daemonSecret');
            unset($data['reset_secret']);
        }

        $oldDaemonKey = $node->daemonSecret;
        $node->update($data);
        try {
            $node->guzzleClient(['X-Access-Token' => $oldDaemonKey])->request('PATCH', '/config', [
                'json' => [
                    'web' => [
                        'listen' => $node->daemonListen,
                        'ssl' => [
                            'enabled' => ($node->scheme === 'https'),
                        ],
                    ],
                    'sftp' => [
                        'path' => $node->daemonBase,
                        'port' => $node->daemonSFTP,
                    ],
                    'remote' => [
                        'base' => config('app.url'),
                        'download' => route('remote.download'),
                        'installed' => route('remote.install'),
                    ],
                    'uploads' => [
                        'size_limit' => $node->upload_size,
                    ],
                    'keys' => [
                        $node->daemonSecret,
                    ],
                ],
            ]);
        } catch (\Exception $ex) {
            throw new DisplayException('Failed to update the node configuration, however your changes have been saved to the database. You will need to manually update the configuration file for the node to apply these changes.');
        }
    }

    /**
     * Adds allocations to a provided node.
     * @param int $id
     * @param array   $data
     */
    public function addAllocations($id, array $data)
    {
        $node = Models\Node::findOrFail($id);

        $validator = Validator::make($data, [
            'allocation_ip' => 'required|string',
            'allocation_alias' => 'sometimes|required|string|max:255',
            'allocation_ports' => 'required|array',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $explode = explode('/', $data['allocation_ip']);
        if (count($explode) !== 1) {
            if (! ctype_digit($explode[1]) || ($explode[1] > 32 || $explode[1] < 25)) {
                throw new DisplayException('CIDR notation only allows masks between /32 and /25.');
            }
        }

        DB::transaction(function () use ($parsed, $node, $data) {
            foreach (Network::parse(gethostbyname($data['allocation_ip'])) as $ip) {
                foreach ($data['allocation_ports'] as $port) {
                    // Determine if this is a valid single port, or a valid port range.
                    if (! ctype_digit($port) && ! preg_match('/^(\d{1,5})-(\d{1,5})$/', $port)) {
                        throw new DisplayException('The mapping for <code>' . $port . '</code> is invalid and cannot be processed.');
                    }

                    if (preg_match('/^(\d{1,5})-(\d{1,5})$/', $port, $matches)) {
                        $block = range($matches[1], $matches[2]);

                        if (count($block) > 1000) {
                            throw new DisplayException('Adding more than 1000 ports at once is not supported. Please use a smaller port range.');
                        }

                        foreach ($block as $unit) {
                            // Insert into Database
                            Models\Allocation::firstOrCreate([
                                'node_id' => $node->id,
                                'ip' => $ip,
                                'port' => $unit,
                                'ip_alias' => isset($data['allocation_alias']) ? $data['allocation_alias'] : null,
                                'server_id' => null,
                            ]);
                        }
                    } else {
                        // Insert into Database
                        Models\Allocation::firstOrCreate([
                            'node_id' => $node->id,
                            'ip' => $ip,
                            'port' => $port,
                            'ip_alias' => isset($data['allocation_alias']) ? $data['allocation_alias'] : null,
                            'server_id' => null,
                        ]);
                    }
                }
            }
        });
    }

    public function delete($id)
    {
        $node = Models\Node::withCount('servers')->findOrFail($id);
        if ($node->servers_count > 0) {
            throw new DisplayException('You cannot delete a node with servers currently attached to it.');
        }

        DB::beginTransaction();

        try {
            // Unlink Database Servers
            Models\DatabaseServer::where('linked_node', $node->id)->update([
                'linked_node' => null,
            ]);

            // Delete Allocations
            Models\Allocation::where('node_id', $node->id)->delete();

            // Delete configure tokens
            Models\NodeConfigurationToken::where('node_id', $node->id)->delete();

            // Delete Node
            $node->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }
}
