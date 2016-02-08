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
                'prefix' => ''
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

        try {

            DB::beginTransaction();

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
                'prefix' => ''
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

}
