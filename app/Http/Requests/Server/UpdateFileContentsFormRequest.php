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

namespace Pterodactyl\Http\Requests\Server;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Log\Writer;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Exceptions\Http\Server\FileTypeNotEditableException;
use Pterodactyl\Http\Requests\FrontendUserFormRequest;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;

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
