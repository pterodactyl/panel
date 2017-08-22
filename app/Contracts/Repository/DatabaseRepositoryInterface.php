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

interface DatabaseRepositoryInterface extends RepositoryInterface
{
    /**
     * Create a new database if it does not already exist on the host with
     * the provided details.
     *
     * @param array $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function createIfNotExists(array $data);

    /**
     * Create a new database on a given connection.
     *
     * @param string      $database
     * @param null|string $connection
     * @return bool
     */
    public function createDatabase($database, $connection = null);

    /**
     * Create a new database user on a given connection.
     *
     * @param string      $username
     * @param string      $remote
     * @param string      $password
     * @param null|string $connection
     * @return bool
     */
    public function createUser($username, $remote, $password, $connection = null);

    /**
     * Give a specific user access to a given database.
     *
     * @param string      $database
     * @param string      $username
     * @param string      $remote
     * @param null|string $connection
     * @return bool
     */
    public function assignUserToDatabase($database, $username, $remote, $connection = null);

    /**
     * Flush the privileges for a given connection.
     *
     * @param null|string $connection
     * @return mixed
     */
    public function flush($connection = null);

    /**
     * Drop a given database on a specific connection.
     *
     * @param string      $database
     * @param null|string $connection
     * @return bool
     */
    public function dropDatabase($database, $connection = null);

    /**
     * Drop a given user on a specific connection.
     *
     * @param string      $username
     * @param string      $remote
     * @param null|string $connection
     * @return mixed
     */
    public function dropUser($username, $remote, $connection = null);
}
