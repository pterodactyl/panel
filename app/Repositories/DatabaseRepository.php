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
use Crypt;
use Config;
use Validator;
use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class DatabaseRepository
{
    /**
     * Adds a new database to a specified database host server.
     *
     * @param int   $server   Id of the server to add a database for.
     * @param array $options  Array of options for creating that database.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     * @throws \Exception
     * @return void
     */
    public function create($server, $data)
    {
        $server = Models\Server::findOrFail($server);

        $validator = Validator::make($data, [
            'host' => 'required|exists:database_servers,id',
            'database' => 'required|regex:/^\w{1,100}$/',
            'connection' => 'required|regex:/^[0-9%.]{1,15}$/',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $host = Models\DatabaseServer::findOrFail($data['host']);
        DB::beginTransaction();

        try {
            $database = Models\Database::firstOrNew([
                'server_id' => $server->id,
                'db_server' => $data['host'],
                'database' => sprintf('s%d_%s', $server->id, $data['database']),
            ]);

            if ($database->exists) {
                throw new DisplayException('A database with those details already exists in the system.');
            }

            $database->username = sprintf('s%d_%s', $server->id, str_random(10));
            $database->remote = $data['connection'];
            $database->password = Crypt::encrypt(str_random(20));

            $database->save();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

        Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => $host->host,
            'port' => $host->port,
            'database' => 'mysql',
            'username' => $host->username,
            'password' => Crypt::decrypt($host->password),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ]);

        try {
            DB::connection('dynamic')->statement(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database->database));
            DB::connection('dynamic')->statement(sprintf(
                'CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\'',
                $database->username, $database->remote, Crypt::decrypt($database->password)
            ));
            DB::connection('dynamic')->statement(sprintf(
                'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON `%s`.* TO `%s`@`%s`',
                $database->database, $database->username, $database->remote
            ));

            DB::connection('dynamic')->statement('FLUSH PRIVILEGES');

            // Save Everything
            DB::commit();
        } catch (\Exception $ex) {
            try {
                DB::connection('dynamic')->statement(sprintf('DROP DATABASE IF EXISTS `%s`', $database->database));
                DB::connection('dynamic')->statement(sprintf('DROP USER IF EXISTS `%s`@`%s`', $database->username, $database->remote));
                DB::connection('dynamic')->statement('FLUSH PRIVILEGES');
            } catch (\Exception $ex) {}

            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Updates the password for a given database.
     * @param  int $id The ID of the database to modify.
     * @param  string $password The new password to use for the database.
     * @return bool
     */
    public function password($id, $password)
    {
        $database = Models\Database::with('host')->findOrFail($id);

        DB::beginTransaction();
        try {
            $database->password = Crypt::encrypt($password);
            $database->save();

            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => $database->host->host,
                'port' => $database->host->port,
                'database' => 'mysql',
                'username' => $database->host->username,
                'password' => Crypt::decrypt($database->host->password),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]);

            DB::connection('dynamic')->statement(sprintf(
                'SET PASSWORD FOR `%s`@`%s` = PASSWORD(\'%s\')',
                $database->username, $database->remote, $password
            ));

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Drops a database from the associated MySQL Server.
     * @param  int $id The ID of the database to drop.
     * @return bool
     */
    public function drop($id)
    {
        $database = Models\Database::with('host')->findOrFail($id);

        DB::beginTransaction();
        try {
            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => $database->host->host,
                'port' => $database->host->port,
                'database' => 'mysql',
                'username' => $database->host->username,
                'password' => Crypt::decrypt($database->host->password),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]);

            DB::connection('dynamic')->statement(sprintf('DROP DATABASE IF EXISTS `%s`', $database->database));
            DB::connection('dynamic')->statement(sprintf('DROP USER IF EXISTS `%s`@`%s`', $database->username, $database->remote));
            DB::connection('dynamic')->statement('FLUSH PRIVILEGES');

            $database->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
     * Deletes a database server from the system if it is empty.
     *
     * @param  int $server The ID of the Database Server.
     * @return
     */
    public function delete($server)
    {
        $host = Models\DatabaseServer::withCount('databases')->findOrFail($server);

        if ($host->databases_count > 0) {
            throw new DisplayException('You cannot delete a database server that has active databases attached to it.');
        }

        return $host->delete();
    }

    /**
     * Adds a new Database Server to the system.
     * @param array $data
     */
    public function add(array $data)
    {
        if (isset($data['host'])) {
            $data['host'] = gethostbyname($data['host']);
        }

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'host' => 'required|ip|unique:database_servers,host',
            'port' => 'required|numeric|between:1,65535',
            'username' => 'required|string|max:32',
            'password' => 'required|string',
            'linked_node' => 'sometimes',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();
        try {
            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => $data['host'],
                'port' => $data['port'],
                'database' => 'mysql',
                'username' => $data['username'],
                'password' => $data['password'],
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]);

            // Allows us to check that we can connect to things.
            DB::connection('dynamic')->select('SELECT 1 FROM dual');

            Models\DatabaseServer::create([
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'],
                'username' => $data['username'],
                'password' => Crypt::encrypt($data['password']),
                'max_databases' => null,
                'linked_node' => (! empty($data['linked_node']) && $data['linked_node'] > 0) ? $data['linked_node'] : null,
            ]);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
