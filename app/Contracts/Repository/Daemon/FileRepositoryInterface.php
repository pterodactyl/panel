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
     * @param string   $path
     * @param int|null $notLargerThan
     * @return string
     */
    public function getContent(string $path, int $notLargerThan = null): string;

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

    /**
     * Creates a new directory for the server in the given $path.
     *
     * @param string $name
     * @param string $path
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createDirectory(string $name, string $path): ResponseInterface;

    /**
     * Renames or moves a file on the remote machine.
     *
     * @param string $from
     * @param string $to
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function renameFile(string $from, string $to): ResponseInterface;

    /**
     * Copy a given file and give it a unique name.
     *
     * @param string $location
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function copyFile(string $location): ResponseInterface;

    /**
     * Delete a file or folder for the server.
     *
     * @param string $location
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function deleteFile(string $location): ResponseInterface;
}
