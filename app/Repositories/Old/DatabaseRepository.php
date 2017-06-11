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
use Validator;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class DatabaseRepository
{
    /**
     * Adds a new database to a specified database host server.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\Database
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create($id, array $data)
    {
        $server = Server::findOrFail($id);

        $validator = Validator::make($data, [
            'host' => 'required|exists:database_hosts,id',
            'database' => 'required|regex:/^\w{1,100}$/',
            'connection' => 'required|regex:/^[0-9%.]{1,15}$/',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        $host = DatabaseHost::findOrFail($data['host']);
        DB::beginTransaction();

        try {
            $database = Database::firstOrNew([
                'server_id' => $server->id,
                'database_host_id' => $data['host'],
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

        try {
            $host->setDynamicConnection();

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

            return $database;
        } catch (\Exception $ex) {
            try {
                DB::connection('dynamic')->statement(sprintf('DROP DATABASE IF EXISTS `%s`', $database->database));
                DB::connection('dynamic')->statement(sprintf('DROP USER IF EXISTS `%s`@`%s`', $database->username, $database->remote));
                DB::connection('dynamic')->statement('FLUSH PRIVILEGES');
            } catch (\Exception $ex) {
            }

            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Updates the password for a given database.
     *
     * @param  int     $id
     * @param  string  $password
     * @return void
     *
     * @todo   Fix logic behind resetting passwords.
     */
    public function password($id, $password)
    {
        $database = Database::with('host')->findOrFail($id);
        $database->host->setDynamicConnection();

        DB::transaction(function () use ($database, $password) {
            $database->password = Crypt::encrypt($password);

            // We have to do the whole delete user, create user thing rather than
            // SET PASSWORD ... because MariaDB and PHP statements ends up inserting
            // a corrupted password. A way around this is strtoupper(sha1(sha1($password, true)))
            // but no garuntees that will work correctly with every system.
            DB::connection('dynamic')->statement(sprintf('DROP USER IF EXISTS `%s`@`%s`', $database->username, $database->remote));
            DB::connection('dynamic')->statement(sprintf(
                'CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\'',
                $database->username, $database->remote, $password
            ));
            DB::connection('dynamic')->statement(sprintf(
                'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON `%s`.* TO `%s`@`%s`',
                $database->database, $database->username, $database->remote
            ));
            DB::connection('dynamic')->statement('FLUSH PRIVILEGES');

            $database->save();
        });
    }

    /**
     * Drops a database from the associated database host.
     *
     * @param  int  $id
     * @return void
     */
    public function drop($id)
    {
        $database = Database::with('host')->findOrFail($id);
        $database->host->setDynamicConnection();

        DB::transaction(function () use ($database) {
            DB::connection('dynamic')->statement(sprintf('DROP DATABASE IF EXISTS `%s`', $database->database));
            DB::connection('dynamic')->statement(sprintf('DROP USER IF EXISTS `%s`@`%s`', $database->username, $database->remote));
            DB::connection('dynamic')->statement('FLUSH PRIVILEGES');

            $database->delete();
        });
    }

    /**
     * Deletes a database host from the system if it has no associated databases.
     *
     * @param  int  $id
     * @return void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        $host = DatabaseHost::withCount('databases')->findOrFail($id);

        if ($host->databases_count > 0) {
            throw new DisplayException('You cannot delete a database host that has active databases attached to it.');
        }

        $host->delete();
    }

    /**
     * Adds a new Database Host to the system.
     *
     * @param  array  $data
     * @return \Pterodactyl\Models\DatabaseHost
     *
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function add(array $data)
    {
        if (isset($data['host'])) {
            $data['host'] = gethostbyname($data['host']);
        }

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'host' => 'required|ip|unique:database_hosts,host',
            'port' => 'required|numeric|between:1,65535',
            'username' => 'required|string|max:32',
            'password' => 'required|string',
            'node_id' => 'sometimes|required|exists:nodes,id',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        return DB::transaction(function () use ($data) {
            $host = new DatabaseHost;
            $host->password = Crypt::encrypt($data['password']);

            $host->fill([
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'],
                'username' => $data['username'],
                'max_databases' => null,
                'node_id' => (isset($data['node_id'])) ? $data['node_id'] : null,
            ])->save();

            // Allows us to check that we can connect to things.
            $host->setDynamicConnection();
            DB::connection('dynamic')->select('SELECT 1 FROM dual');

            return $host;
        });
    }

    /**
     * Updates a Database Host on the system.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\DatabaseHost
     *
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function update($id, array $data)
    {
        $host = DatabaseHost::findOrFail($id);

        if (isset($data['host'])) {
            $data['host'] = gethostbyname($data['host']);
        }

        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|max:255',
            'host' => 'sometimes|required|ip|unique:database_hosts,host,' . $host->id,
            'port' => 'sometimes|required|numeric|between:1,65535',
            'username' => 'sometimes|required|string|max:32',
            'password' => 'sometimes|required|string',
            'node_id' => 'sometimes|required|exists:nodes,id',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        return DB::transaction(function () use ($data, $host) {
            if (isset($data['password'])) {
                $host->password = Crypt::encrypt($data['password']);
            }
            $host->fill($data)->save();

            // Check that we can still connect with these details.
            $host->setDynamicConnection();
            DB::connection('dynamic')->select('SELECT 1 FROM dual');

            return $host;
        });
    }
}
