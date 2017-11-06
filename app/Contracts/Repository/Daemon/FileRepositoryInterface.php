<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository\Daemon;

interface FileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Return stat information for a given file.
     *
     * @param string $path
     * @return object
     *
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function getFileStat($path);

    /**
     * Return the contents of a given file if it can be edited in the Panel.
     *
     * @param string $path
     * @return object
     *
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function getContent($path);

    /**
     * Save new contents to a given file.
     *
     * @param string $path
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function putContent($path, $content);

    /**
     * Return a directory listing for a given path.
     *
     * @param string $path
     * @return array
     *
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function getDirectory($path);
}
