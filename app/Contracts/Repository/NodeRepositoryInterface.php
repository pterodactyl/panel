<?php
/*
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

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Contracts\Repository\Attributes\SearchableInterface;

interface NodeRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return the usage stats for a single node.
     *
     * @param int $id
     * @return array
     */
    public function getUsageStats($id);

    /**
     * Return all available nodes with a searchable interface.
     *
     * @param int $count
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNodeListingData($count = 25);

    /**
     * Return a single node with location and server information.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getSingleNode($id);

    /**
     * Return a node with all of the associated allocations and servers that are attached to said allocations.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getNodeAllocations($id);

    /**
     * Return a node with all of the servers attached to that node.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getNodeServers($id);

    /**
     * Return a collection of nodes beloning to a specific location for use on frontend display.
     *
     * @param int $location
     * @return mixed
     */
    public function getNodesForLocation($location);
}
