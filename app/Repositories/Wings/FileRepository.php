<?php

namespace Pterodactyl\Repositories\Wings;

use stdClass;
use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;

class FileRepository extends BaseWingsRepository implements FileRepositoryInterface
{
    /**
     * Return stat information for a given file.
     *
     * @param string $path
     * @return \stdClass
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getFileStat(string $path): stdClass
    {
        // TODO: Implement getFileStat() method.
    }

    /**
     * Return the contents of a given file if it can be edited in the Panel.
     *
     * @param string $path
     * @return string
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getContent(string $path): string
    {
        // TODO: Implement getContent() method.
    }

    /**
     * Save new contents to a given file.
     *
     * @param string $path
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function putContent(string $path, string $content): ResponseInterface
    {
        // TODO: Implement putContent() method.
    }

    /**
     * Return a directory listing for a given path.
     *
     * @param string $path
     * @return array
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getDirectory(string $path): array
    {
        $response = $this->getHttpClient()->get(
            // Reason for the path check is because it is unnecessary on the Daemon but we need
            // to respect the interface.
            sprintf('/api/servers/%s/files/list/%s', $this->getServer()->uuid, $path === '/' ? '' : $path)
        );

        return json_decode($response->getBody(), true);
    }
}
