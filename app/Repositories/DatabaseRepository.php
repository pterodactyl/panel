<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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

use Crypt;
use Log;
use DB;
use Validator;

use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseRepository {

    /**
     * Adds a new database to a given database server.
     * @param int   $server   Id of the server to add a database for.
     * @param array $options  Array of options for creating that database.
     * @return void
     */
    public function create($server, $options)
    {
        $server = Models\Server::findOrFail($server);
        $validator = Validator::make($options, [
            'db_server' => 'required|exists:database_servers,id',
            'database' => 'required|regex:/^\w{1,100}$/',
            'remote' => 'required|regex:/^[0-9%.]{1,15}$/',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();

        try {
            $db = new Models\Database;
            $db->fill([
                'server' => $server->id,
                'db_server' => $options['db_server'],
                'database' => $server->uuidShort . '_' . $options['database'],
                'username' => $server->uuidShort . '_' . str_random(7),
                'remote' => $options['remote'],
                'password' => Crypt::encrypt(str_random(20))
            ]);
            $db->save();

            // Contact Remote
            $dbr = Models\DatabaseServer::findOrFail($options['db_server']);

            $capsule = new Capsule;
            $capsule->addConnection([
                'driver' => 'mysql',
                'host' => $dbr->host,
                'port' => $dbr->port,
                'database' => 'mysql',
                'username' => $dbr->username,
                'password' => Crypt::decrypt($dbr->password),
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
                'options' => [
                    \PDO::ATTR_TIMEOUT => 3,
                ]
            ]);

            $capsule->setAsGlobal();

            Capsule::statement('CREATE DATABASE ' . $db->database);
            Capsule::statement('CREATE USER \'' . $db->username . '\'@\'' . $db->remote . '\' IDENTIFIED BY \'' . Crypt::decrypt($db->password) . '\'');
            Capsule::statement('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON ' . $db->database . '.* TO \'' . $db->username . '\'@\'' . $db->remote . '\'');
            Capsule::statement('FLUSH PRIVILEGES');

            DB::commit();
            return true;
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
     * Drops a database from the associated MySQL Server
     * @param  int $database The ID of the database to drop.
     * @return boolean
     */
    public function drop($database)
    {
        $db = Models\Database::findOrFail($database);
        $dbr = Models\DatabaseServer::findOrFail($db->db_server);

        DB::beginTransaction();

        try {
            $capsule = new Capsule;
            $capsule->addConnection([
                'driver' => 'mysql',
                'host' => $dbr->host,
                'port' => $dbr->port,
                'database' => 'mysql',
                'username' => $dbr->username,
                'password' => Crypt::decrypt($dbr->password),
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
                'options' => [
                    \PDO::ATTR_TIMEOUT => 3,
                ]
            ]);

            $capsule->setAsGlobal();

            Capsule::statement('DROP USER \'' . $db->username . '\'@\'' . $db->remote . '\'');
            Capsule::statement('DROP DATABASE ' . $db->database);

            $db->delete();

            DB::commit();
            return true;
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }

    }

    /**
     * Deletes a database server from the system if it is empty.
     * @param  int $server The ID of the Database Server.
     * @return
     */
    public function delete($server)
    {
        $dbh = Models\DatabaseServer::findOrFail($server);
        $databases = Models\Database::where('db_server', $dbh->id)->count();

        if ($databases > 0) {
            throw new DisplayException('You cannot delete a database server that has active databases attached to it.');
        }

        return $dbh->delete();
    }

    /**
     * Adds a new Database Server to the system.
     * @param array $data
     */
    public function add(array $data)
    {
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
            $capsule = new Capsule;
            $capsule->addConnection([
                'driver' => 'mysql',
                'host' => $data['host'],
                'port' => $data['port'],
                'database' => 'mysql',
                'username' => $data['username'],
                'password' => $data['password'],
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
                'options' => [
                    \PDO::ATTR_TIMEOUT => 3,
                ]
            ]);

            $capsule->setAsGlobal();

            // Allows us to check that we can connect to things.
            Capsule::select('SELECT 1 FROM dual');

            $dbh = new Models\DatabaseServer;
            $dbh->fill([
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'],
                'username' => $data['username'],
                'password' => Crypt::encrypt($data['password']),
                'max_databases' => NULL,
                'linked_node' => (!empty($data['linked_node']) && $data['linked_node'] > 0) ? $data['linked_node'] : NULL
            ]);
            $dbh->save();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

}
