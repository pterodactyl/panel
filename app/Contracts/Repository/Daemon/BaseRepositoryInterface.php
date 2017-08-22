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

interface BaseRepositoryInterface
{
    /**
     * Set the node model to be used for this daemon connection.
     *
     * @param int $id
     * @return $this
     */
    public function setNode($id);

    /**
     * Return the node model being used.
     *
     * @return \Pterodactyl\Models\Node
     */
    public function getNode();

    /**
     * Set the UUID for the server to be used in the X-Access-Server header for daemon requests.
     *
     * @param null|string $server
     * @return $this
     */
    public function setAccessServer($server = null);

    /**
     * Return the UUID of the server being used in requests.
     *
     * @return string
     */
    public function getAccessServer();

    /**
     * Set the token to be used in the X-Access-Token header for requests to the daemon.
     *
     * @param null|string $token
     * @return $this
     */
    public function setAccessToken($token = null);

    /**
     * Return the access token being used for requests.
     *
     * @return string
     */
    public function getAccessToken();

    /**
     * Return an instance of the Guzzle HTTP Client to be used for requests.
     *
     * @param array $headers
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient($headers = []);
}
