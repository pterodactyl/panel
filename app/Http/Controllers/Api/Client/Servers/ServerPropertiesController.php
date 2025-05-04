<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Support\Arr;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Requests\Api\Client\Servers\ServerPropertiesRequest;

class ServerPropertiesController extends ClientApiController
{
    /**
     * @param DaemonFileRepository $daemonFileRepository
     */
    public function __construct(private DaemonFileRepository $daemonFileRepository)
    {
        parent::__construct();
    }

    /**
     * @param ServerPropertiesRequest $request
     * @param Server $server
     * @return array[]
     * @throws DisplayException
     */
    public function index(ServerPropertiesRequest $request, Server $server)
    {
        try {
            $properties = $this->daemonFileRepository->setServer($server)->getContent('server.properties');
        } catch (DaemonConnectionException|FileSizeTooLargeException $e) {
            throw new DisplayException('Failed to load the server properties.');
        }

        $values = [];
        $initial = [];

        foreach (explode(PHP_EOL, $properties) as $line) {
            if (empty($line) || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            $type = 'string';
            $selectValues = [];
            $exp = explode('=', $line);

            // Select detection
            switch ($exp[0]) {
                case 'difficulty':
                    $type = 'select';
                    $selectValues = ['peaceful', 'easy', 'normal', 'hard'];
                    break;
                case 'gamemode':
                    $type = 'select';
                    $selectValues = ['survival', 'creative', 'hardcode', 'adventure', 'spectator'];
                    break;
            }

            // Switch detection
            switch ($exp[1] ?? '') {
                case 'true':
                case 'false':
                    $type = 'switch';
                    break;
            }

            $values[] = [
                'key' => str_replace('.', '-', $exp[0]),
                'value' => $exp[1] ?? '',
                'type' => $type,
                'values' => $selectValues,
            ];

            $initial[str_replace('.', '-', $exp[0])] = $type == 'switch' ? (($exp[1] ?? '') == 'true') : $exp[1] ?? '';
        }

        return [
            'properties' => $values,
            'initial' => $initial,
        ];
    }

    /**
     * @param ServerPropertiesRequest $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     */
    public function save(ServerPropertiesRequest $request, Server $server)
    {
        try {
            $properties = $this->daemonFileRepository->setServer($server)->getContent('server.properties');
        } catch (DaemonConnectionException|FileSizeTooLargeException $e) {
            throw new DisplayException('Failed to load the server properties.');
        }

        $lines = explode(PHP_EOL, $properties);
        $values = $request->input('values', []);

        foreach ($lines as $key => $line) {
            if (empty($line) || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            $exp = explode('=', $line);

            $lines[$key] = $exp[0] . '=' . Arr::get($values, str_replace('.', '-', $exp[0]), $exp[1] ?? '');
        }

        try {
            $this->daemonFileRepository->setServer($server)->putContent('server.properties', implode(PHP_EOL, $lines));
        } catch (DaemonConnectionException $e) {
            throw new DisplayException('Failed to save the properties. Please try again...');
        }

        return [];
    }
}
