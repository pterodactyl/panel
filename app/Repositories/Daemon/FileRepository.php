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
namespace Pterodactyl\Repositories\Daemon;

use \Exception;
use Log;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;
use Pterodactyl\Repositories\HelperRepository;
use Pterodactyl\Exceptions\DisplayException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class FileRepository
{

    /**
     * The Eloquent Model associated with the requested server.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $server;

    /**
     * The Eloquent Model for the node corresponding with the requested server.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $node;

    /**
     * The Guzzle Client associated with the requested server and node.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The Guzzle Client headers associated with the requested server and node.
     * (non-administrative headers)
     *
     * @var array
     */
    protected $headers;

    /**
     * Constructor
     *
     * @param string $server The server Short UUID
     */
    public function __construct($uuid)
    {

        $this->server = Server::getByUUID($uuid);
        $this->node = Node::getByID($this->server->node);
        $this->client = Node::guzzleRequest($this->server->node);
        $this->headers = Server::getGuzzleHeaders($uuid);

    }

    /**
     * Get the contents of a requested file for the server.
     *
     * @param  string $file
     * @return array
     */
    public function returnFileContents($file)
    {

        if (empty($file)) {
            throw new Exception('Not all parameters were properly passed to the function.');
        }

        $file = (object) pathinfo($file);

        $file->dirname = (in_array($file->dirname, ['.', './', '/'])) ? null : trim($file->dirname, '/') . '/';

        $res = $this->client->request('GET', '/server/file/stat/' . rawurlencode($file->dirname.$file->basename) , [
            'headers' => $this->headers
        ]);

        $stat = json_decode($res->getBody());
        if($res->getStatusCode() !== 200 || !isset($stat->size)) {
            throw new DisplayException('The daemon provided a non-200 error code on stat lookup: HTTP\\' . $res->getStatusCode());
        }

        if (!in_array($stat->mime, HelperRepository::editableFiles())) {
            throw new DisplayException('You cannot edit that type of file (' . $stat->mime . ') through the panel.');
        }

        if ($stat->size > 5000000) {
            throw new DisplayException('That file is too large to open in the browser, consider using a SFTP client.');
        }

        $res = $this->client->request('GET', '/server/file/f/' . rawurlencode($file->dirname.$file->basename) , [
            'headers' => $this->headers
        ]);

        $json = json_decode($res->getBody());
        if($res->getStatusCode() !== 200 || !isset($json->content)) {
            throw new DisplayException('The daemon provided a non-200 error code: HTTP\\' . $res->getStatusCode());
        }

        return [
            'file' => $json,
            'stat' => $stat
        ];

    }

    /**
     * Save the contents of a requested file on the daemon.
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function saveFileContents($file, $content)
    {

        if (empty($file)) {
            throw new Exception('A valid file and path must be specified to save a file.');
        }

        $file = (object) pathinfo($file);

        $file->dirname = (in_array($file->dirname, ['.', './', '/'])) ? null : trim($file->dirname, '/') . '/';

        $res = $this->client->request('POST', '/server/file/save', [
            'headers' => $this->headers,
            'json' => [
                'path' => rawurlencode($file->dirname.$file->basename),
                'content' => $content
            ]
        ]);

        if ($res->getStatusCode() !== 204) {
            throw new DisplayException('An error occured while attempting to save this file. ' . $res->getBody());
        }

        return true;

    }

    /**
     * Returns a listing of all files and folders within a specified directory on the daemon.
     *
     * @param  string $directory
     * @return object
     */
    public function returnDirectoryListing($directory)
    {

        if (empty($directory)) {
            throw new Exception('A valid directory must be specified in order to list its contents.');
        }

        $res = $this->client->request('GET', '/server/directory/' . rawurlencode($directory), [
            'headers' => $this->headers
        ]);

        $json = json_decode($res->getBody());
        if($res->getStatusCode() !== 200) {
            throw new DisplayException('An error occured while attempting to save this file. ' . $res->getBody());
        }

        // Iterate through results
        $files = [];
        $folders = [];
        foreach($json as &$value) {

            if ($value->directory === true) {

                // @TODO Handle Symlinks
                $folders = array_merge($folders, [[
                    'entry' => $value->name,
                    'directory' => trim($directory, '/'),
                    'size' => null,
                    'date' => strtotime($value->modified),
                    'mime' => $value->mime
                ]]);

            } else if ($value->file === true) {

                $files = array_merge($files, [[
                    'entry' => $value->name,
                    'directory' => trim($directory, '/'),
                    'extension' => pathinfo($value->name, PATHINFO_EXTENSION),
                    'size' => HelperRepository::bytesToHuman($value->size),
                    'date' => strtotime($value->modified),
                    'mime' => $value->mime
                ]]);

            }

        }

        return (object) [
            'files' => $files,
            'folders' => $folders,
        ];

    }

}
