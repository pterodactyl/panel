<?php

namespace Pterodactyl\Services\Minecraft;

use GuzzleHttp\Client;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\ClientException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class MinecraftSoftwareService
{
    protected Client $client;
    protected ?Server $server;

    public function __construct(protected DaemonFileRepository $daemonFileRepository)
    {
        $this->client = new Client([
            'base_uri' => 'https://versions.mcjars.app/',
        ]);
    }

    /**
     * Set the server model this request is stemming from.
     *
     * @return $this
     */
    public function setServer(Server $server): self
    {
        $this->server = $server;
        $this->daemonFileRepository->setServer($server);

        return $this;
    }

    public function getServerBuildInformation(): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        $files = $this->daemonFileRepository->getDirectory('/');

        // If the server has a jar file in its file root, we check for that first.
        $variable = $this->server->variables()->where('env_variable', 'SERVER_JARFILE')->first();
        $jarFile = $variable ? ($variable->server_value ?? $variable->default_value) : 'server.jar';

        $data = [
            'buildType' => 'UNKNOWN',
        ];

        foreach ($files as $file) {
            if ($file['name'] === $jarFile) {
                $hash = $this->daemonFileRepository->getFingerprints([$file['name']])[$file['name']];

                $data = $this->getBuildInformationFromHash($hash);
                break;
            }
        }

        // Otherwise, the server generally has a `unix_args.txt` that makes it run
        // some jar in `libraries`, might be Forge or NeoForge...
        if ($data['buildType'] === 'UNKNOWN') {
            // Try to detect if it's Forge
            // We want to make the least network calls possible
            try {
                $forgeDirectory = 'libraries/net/minecraftforge/forge';
                $files = $this->daemonFileRepository->getDirectory($forgeDirectory);

                if (isset($files[0])) {
                    $fileName = $forgeDirectory . '/' . $files[0]['name'] . '/forge-' . $files[0]['name'] . '-server.jar';
                    $hash = $this->daemonFileRepository->getFingerprints([$fileName])[$fileName];

                    $data = $this->getBuildInformationFromHash($hash);
                }
            } catch (DaemonConnectionException) {
            }

            // If we still don't have anything, try the NeoForge path.
            if ($data['buildType'] === 'UNKNOWN') {
                try {
                    $neoforgeDirectory = 'libraries/net/neoforged/neoforge';
                    $files = $this->daemonFileRepository->getDirectory($neoforgeDirectory);

                    if (isset($files[0])) {
                        $fileName = $neoforgeDirectory . '/' . $files[0]['name'] . '/neoforge-' . $files[0]['name'] . '-server.jar';
                        $hash = $this->daemonFileRepository->getFingerprints([$fileName])[$fileName];

                        $data = $this->getBuildInformationFromHash($hash);
                    }
                } catch (DaemonConnectionException) {
                }
            }
        }

        return $data;
    }

    /**
     * @param $hash string must be SHA512
     */
    protected function getBuildInformationFromHash(string $hash): array
    {
        return Cache::remember('minecraft-build-hash-' . $hash, 3600 * 24 * 7, function () use ($hash) {
            try {
                $res = $this->client->post('api/v2/build', [
                    'json' => [
                        'hash' => [
                            'sha512' => $hash,
                        ],
                    ],
                ]);
            } catch (ClientException) {
                return [
                    'buildType' => 'UNKNOWN',
                ];
            }
            $data = json_decode($res->getBody()->getContents(), true);

            $type = $data['build']['type'];
            $versionName = $data['build']['versionId'] ?? $data['build']['projectVersionId'];

            $buildInformation = [
                'buildType' => $type,
                'versionName' => $versionName,
                'java' => $data['version']['java'] ?? null,
            ];

            return $buildInformation;
        });
    }

    /**
     * Get paths of files in $directory.
     *
     * @return string[]
     */
    public function getModsPaths(string $directory = 'mods'): array
    {
        $paths = [];
        try {
            $entries = $this->daemonFileRepository->getDirectory($directory);

            $paths = array_map(fn ($v) => $directory . '/' . $v['name'], array_filter($entries, fn ($e) => $e['file']));
        } catch (DaemonConnectionException) {
        }

        return $paths;
    }

    /**
     * Returns normalized mod versions from hashes in $directory.
     */
    public function getInstalledProjectsVersions(string $directory): array
    {
        $tempVersions = [
            'other' => [],
        ];

        /** @var ModrinthMinecraftService */
        $modrinthService = app(ModrinthMinecraftService::class);
        $serverBuildInfo = $this->getServerBuildInformation();
        $paths = $this->getModsPaths($directory);
        $modrinthVersions = $modrinthService->getModsVersions($this->server, $paths, $serverBuildInfo);

        $tempVersions['identified'] = $modrinthVersions['identified'];

        if (! empty($modrinthVersions['other'])) {
            /** @var CurseForgeMinecraftService */
            $curseForgeService = app(CurseForgeMinecraftService::class);
            $curseForgeVersions = $curseForgeService->getModsVersions($this->server, $modrinthVersions['other'], $serverBuildInfo);

            $tempVersions['identified'] = array_merge($tempVersions['identified'], $curseForgeVersions['identified']);
            $tempVersions['other'] = array_map(function ($o) {
                return [
                    'path' => $o,
                    'provider' => null,
                    'project_id' => null,
                    'project_name' => null,
                    'version_id' => null,
                    'version_name' => null,
                    'icon_url' => null,
                    'update' => null,
                ];
            }, $curseForgeVersions['other']);
        }

        $versions = array_merge($tempVersions['identified'], $tempVersions['other']);

        usort($versions, fn ($a, $b) => strcmp(
            strtolower($a['project_name'] ?? $a['path']),
            strtolower($b['project_name'] ?? $b['path'])
        ));

        return $versions;
    }
}
