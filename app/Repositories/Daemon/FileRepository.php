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

namespace Pterodactyl\Repositories\Daemon;

use Exception;
use GuzzleHttp\Client;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\HelperRepository;

class FileRepository
{
    /**
     * The Eloquent Model associated with the requested server.
     *
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * Constructor.
     *
     * @param  string  $uuid
     * @return void
     */
    public function __construct($uuid)
    {
        $this->server = Server::byUuid($uuid);
    }

    /**
     * Get the contents of a requested file for the server.
     *
     * @param  string  $file
     * @return array
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function returnFileContents($file)
    {
        if (empty($file)) {
            throw new Exception('Not all parameters were properly passed to the function.');
        }

        $file = (object) pathinfo($file);
        $file->dirname = (in_array($file->dirname, ['.', './', '/'])) ? null : trim($file->dirname, '/') . '/';

        $res = $this->server->guzzleClient()->request('GET', '/server/file/stat/' . rawurlencode($file->dirname . $file->basename));

        $stat = json_decode($res->getBody());
        if ($res->getStatusCode() !== 200 || ! isset($stat->size)) {
            throw new DisplayException('The daemon provided a non-200 error code on stat lookup: HTTP\\' . $res->getStatusCode());
        }

        if (! in_array($stat->mime, HelperRepository::editableFiles())) {
            throw new DisplayException('You cannot edit that type of file (' . $stat->mime . ') through the panel.');
        }

        if ($stat->size > 5000000) {
            throw new DisplayException('That file is too large to open in the browser, consider using a SFTP client.');
        }

        $res = $this->server->guzzleClient()->request('GET', '/server/file/f/' . rawurlencode($file->dirname . $file->basename));

        $json = json_decode($res->getBody());
        if ($res->getStatusCode() !== 200 || ! isset($json->content)) {
            throw new DisplayException('The daemon provided a non-200 error code: HTTP\\' . $res->getStatusCode());
        }

        return [
            'file' => $json,
            'stat' => $stat,
        ];
    }

    /**
     * Save the contents of a requested file on the daemon.
     *
     * @param  string  $file
     * @param  string  $content
     * @return bool
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function saveFileContents($file, $content)
    {
        if (empty($file)) {
            throw new Exception('A valid file and path must be specified to save a file.');
        }

        $file = (object) pathinfo($file);
        $file->dirname = (in_array($file->dirname, ['.', './', '/'])) ? null : trim($file->dirname, '/') . '/';

        $res = $this->server->guzzleClient()->request('POST', '/server/file/save', [
            'json' => [
                'path' => rawurlencode($file->dirname . $file->basename),
                'content' => $content,
            ],
        ]);

        if ($res->getStatusCode() !== 204) {
            throw new DisplayException('An error occured while attempting to save this file. ' . $res->getBody());
        }

        return true;
    }

    /**
     * Returns a listing of all files and folders within a specified directory on the daemon.
     *
     * @param  string  $directory
     * @return object
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function returnDirectoryListing($directory)
    {
        if (empty($directory)) {
            throw new Exception('A valid directory must be specified in order to list its contents.');
        }

        try {
            $res = $this->server->guzzleClient()->request('GET', '/server/directory/' . rawurlencode($directory));
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            $json = json_decode($ex->getResponse()->getBody());

            throw new DisplayException($json->error);
        } catch (\GuzzleHttp\Exception\ServerException $ex) {
            throw new DisplayException('A remote server error was encountered while attempting to display this directory.');
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            throw new DisplayException('A ConnectException was encountered: unable to contact daemon.');
        } catch (\Exception $ex) {
            throw $ex;
        }

        $json = json_decode($res->getBody());

        // Iterate through results
        $files = [];
        $folders = [];
        foreach ($json as &$value) {
            if ($value->directory) {
                // @TODO Handle Symlinks
                $folders[] = [
                    'entry' => $value->name,
                    'directory' => trim($directory, '/'),
                    'size' => null,
                    'date' => strtotime($value->modified),
                    'mime' => $value->mime,
                ];
            } elseif ($value->file) {
                $files[] = [
                    'entry' => $value->name,
                    'directory' => trim($directory, '/'),
                    'extension' => pathinfo($value->name, PATHINFO_EXTENSION),
                    'size' => HelperRepository::bytesToHuman($value->size),
                    'date' => strtotime($value->modified),
                    'mime' => $value->mime,
                ];
            }
        }

        return (object) [
            'files' => $files,
            'folders' => $folders,
        ];
    }
}
