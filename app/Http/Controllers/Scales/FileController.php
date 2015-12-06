<?php

namespace Pterodactyl\Http\Controllers\Scales;

use \Exception;
use Log;
use Debugbar;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Helpers;
use Pterodactyl\Http\Controllers\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class FileController extends Controller
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
     * @return string
     */
    public function returnFileContents($file)
    {

        if (empty($file)) {
            throw new Exception('Not all parameters were properly passed to the function.');
        }

        $file = (object) pathinfo($file);
        if (!in_array($file->extension, Helpers::editableFiles())) {
            throw new DisplayException('You do not have permission to edit this type of file.');
        }

        $file->dirname = (in_array($file->dirname, ['.', './', '/'])) ? null : trim($file->dirname, '/') . '/';

        $res = $this->client->request('GET', '/server/file/' . rawurlencode($file->dirname.$file->basename), [
            'headers' => $this->headers
        ]);

        $json = json_decode($res->getBody());
        if($res->getStatusCode() !== 200 || !isset($json->contents)) {
            throw new DisplayException('Scales provided a non-200 error code: HTTP\\' . $res->getStatusCode());
        }

        return $json;

    }

    /**
     * Save the contents of a requested file on the Scales instance.
     *
     * @param  string $file
     * @param  string $content
     * @return boolean
     */
    public function saveFileContents($file, $content)
    {

        if (empty($file)) {
            throw new Exception('A valid file and path must be specified to save a file.');
        }

        $file = (object) pathinfo($file);

        if(!in_array($file->extension, Helpers::editableFiles())) {
            throw new DisplayException('You do not have permission to edit this type of file.');
        }

        $file->dirname = (in_array($file->dirname, ['.', './', '/'])) ? null : trim($file->dirname, '/') . '/';

        $res = $this->client->request('PUT', '/server/file/' . rawurlencode($file->dirname.$file->basename), [
            'headers' => $this->headers,
            'form_params' => [
                'contents' => $content
            ]
        ]);

        if ($res->getStatusCode() !== 204) {
            throw new DisplayException('An error occured while attempting to save this file. Scales said: ' . $res->getBody());
        }

        return true;

    }

    /**
     * Returns a listing of all files and folders within a specified Scales directory.
     *
     * @param  string $directory
     * @return object
     */
    public function returnDirectoryListing($directory)
    {

        if (empty($directory)) {
            throw new Exception('A valid directory must be specified in order to list its contents.');
        }

        $res = $this->client->request('GET', '/server/directory/' . $directory, [
            'headers' => $this->headers
        ]);

        $json = json_decode($res->getBody());
        if($res->getStatusCode() !== 200) {
            throw new DisplayException('An error occured while attempting to save this file. Scales said: ' . $res->getBody());
        }

        // Iterate through results
        $files = [];
        $folders = [];
        foreach($json as &$value) {

            if ($value->file !== true) {

                // @TODO Handle Symlinks
                $folders = array_merge($folders, [[
                    'entry' => $value->name,
                    'directory' => trim($directory, '/'),
                    'size' => null,
                    'date' => strtotime($value->modified)
                ]]);

            } else {

                $files = array_merge($files, [[
                    'entry' => $value->name,
                    'directory' => trim($directory, '/'),
                    'extension' => pathinfo($value->name, PATHINFO_EXTENSION),
                    'size' => Helpers::bytesToHuman($value->size),
                    'date' => strtotime($value->modified)
                ]]);

            }

        }

        return (object) [
            'files' => $files,
            'folders' => $folders,
        ];

    }

}
