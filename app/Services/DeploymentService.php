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

namespace Pterodactyl\Services;

use DB;
use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayException;

class DeploymentService
{
    public function __constructor()
    {
        //
    }

    /**
     * Return a random location model. DO NOT USE.
     * @return \Pterodactyl\Models\Node
     *
     * @TODO Actually make this smarter. If we're selecting a random location
     * but then it has no nodes we should probably continue attempting all locations
     * until we hit one.
     *
     * Currently you should just pick a location and go from there.
     */
    public static function randomLocation()
    {
        return Models\Location::inRandomOrder()->first();
    }

    /**
     * Return a model instance of a random node.
     * @param  int $location
     * @param  array $not
     * @return \Pterodactyl\Models\Node
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public static function randomNode($location, array $not = [])
    {
        $useLocation = Models\Location::where('id', $location)->first();
        if (! $useLocation) {
            throw new DisplayException('The location passed was not valid and could not be found.');
        }

        $node = Models\Node::where('location_id', $useLocation->id)->where('public', 1)->whereNotIn('id', $not)->inRandomOrder()->first();
        if (! $node) {
            throw new DisplayException("Unable to find a node in location {$useLocation->short} (id: {$useLocation->id}) that is available and has space.");
        }

        return $node;
    }

    /**
     * Selects a random node ensuring it does not put the node
     * over allocation limits.
     * @param  int $memory
     * @param  int $disk
     * @param  int $location
     * @return \Pterodactyl\Models\Node
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public static function smartRandomNode($memory, $disk, $location = null)
    {
        $node = self::randomNode($location);
        $notIn = [];
        do {
            $return = self::checkNodeAllocation($node, $memory, $disk);
            if (! $return) {
                $notIn = array_merge($notIn, [
                    $node->id,
                ]);
                $node = self::randomNode($location, $notIn);
            }
        } while (! $return);

        return $node;
    }

    /**
     * Returns a random allocation for a node.
     * @param  int $node
     * @return \Models\Pterodactyl\Allocation
     */
    public static function randomAllocation($node)
    {
        $allocation = Models\Allocation::where('node_id', $node)->whereNull('server_id')->inRandomOrder()->first();
        if (! $allocation) {
            throw new DisplayException('No available allocation could be found for the assigned node.');
        }

        return $allocation;
    }

    /**
     * Checks that a node's allocation limits will not be passed with the given information.
     * @param  \Pterodactyl\Models\Node $node
     * @param  int $memory
     * @param  int $disk
     * @return bool Returns true if this information would not put the node over it's limit.
     */
    protected static function checkNodeAllocation(Models\Node $node, $memory, $disk)
    {
        if (is_numeric($node->memory_overallocate) || is_numeric($node->disk_overallocate)) {
            $totals = Models\Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node_id', $node->id)->first();

            // Check memory limits
            if (is_numeric($node->memory_overallocate)) {
                $limit = ($node->memory * (1 + ($node->memory_overallocate / 100)));
                $memoryLimitReached = (($totals->memory + $memory) > $limit);
            }

            // Check Disk Limits
            if (is_numeric($node->disk_overallocate)) {
                $limit = ($node->disk * (1 + ($node->disk_overallocate / 100)));
                $diskLimitReached = (($totals->disk + $disk) > $limit);
            }

            return ! $diskLimitReached && ! $memoryLimitReached;
        }
    }
}
