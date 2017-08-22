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

namespace Pterodactyl\Contracts\Repository\Daemon;

interface ServerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Create a new server on the daemon for the panel.
     *
     * @param int   $id
     * @param array $overrides
     * @param bool  $start
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function create($id, $overrides = [], $start = false);

    /**
     * Update server details on the daemon.
     *
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(array $data);

    /**
     * Mark a server to be reinstalled on the system.
     *
     * @param array|null $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function reinstall($data = null);

    /**
     * Mark a server as needing a container rebuild the next time the server is booted.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function rebuild();

    /**
     * Suspend a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function suspend();

    /**
     * Un-suspend a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function unsuspend();

    /**
     * Delete a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete();
}
