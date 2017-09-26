<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Server;

use Illuminate\Log\Writer;
use Illuminate\Contracts\Session\Session;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\FrontendUserFormRequest;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Exceptions\Http\Server\FileTypeNotEditableException;

class UpdateFileContentsFormRequest extends FrontendUserFormRequest
{
    /**
     * @var object
     */
    protected $stats;

    /**
     * Authorize a request to edit a file.
     *
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException
     * @throws \Pterodactyl\Exceptions\Http\Server\FileTypeNotEditableException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function authorize()
    {
        parent::authorize();

        $session = app()->make(Session::class);
        $server = $session->get('server_data.model');
        $token = $session->get('server_data.token');

        $permission = $this->user()->can('edit-files', $server);
        if (! $permission) {
            return false;
        }

        return $this->checkFileCanBeEdited($server, $token);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Return the file stats from the Daemon.
     *
     * @return object
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @param \Pterodactyl\Models\Server $server
     * @param string                     $token
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException
     * @throws \Pterodactyl\Exceptions\Http\Server\FileTypeNotEditableException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    protected function checkFileCanBeEdited($server, $token)
    {
        $config = app()->make(Repository::class);
        $repository = app()->make(FileRepositoryInterface::class);

        try {
            $this->stats = $repository->setNode($server->node_id)
                ->setAccessServer($server->uuid)
                ->setAccessToken($token)
                ->getFileStat($this->route()->parameter('file'));
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            app()->make(Writer::class)->warning($exception);

            throw new DisplayException(trans('exceptions.daemon_connection_failed', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        if (! $this->stats->file || ! in_array($this->stats->mime, $config->get('pterodactyl.files.editable'))) {
            throw new FileTypeNotEditableException(trans('server.files.exceptions.invalid_mime'));
        }

        if ($this->stats->size > $config->get('pterodactyl.files.max_edit_size')) {
            throw new FileSizeTooLargeException(trans('server.files.exceptions.max_size'));
        }

        return true;
    }
}
