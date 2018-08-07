<?php

namespace Pterodactyl\Contracts\Repository\Daemon;

use stdClass;
use Psr\Http\Message\ResponseInterface;

interface FileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Return stat information for a given file.
     *
     * @param string $path
     * @return \stdClass
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getFileStat(string $path): stdClass;

    /**
     * Return the contents of a given file if it can be edited in the Panel.
     *
     * @param string $path
     * @return string
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getContent(string $path): string;

    /**
     * Save new contents to a given file.
     *
     * @param string $path
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function putContent(string $path, string $content): ResponseInterface;

    /**
     * Return a directory listing for a given path.
     *
     * @param string $path
     * @return array
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getDirectory(string $path): array;
}
