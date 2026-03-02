<?php

namespace Pterodactyl\Services\WorldManager;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class CurseForgeService
{
    private const BASE_URL = 'https://api.curseforge.com/v1';
    private const GAME_ID_MINECRAFT = 432;
    private const CLASS_ID_WORLDS = 17;

    public function __construct(private Factory $http)
    {
    }

    public function searchWorlds(string $query, ?string $minecraftVersion, int $page, int $pageSize, string $sortField): array
    {
        $cacheKey = sprintf(
            'world-manager:search:%s:%s:%d:%d:%s',
            md5($query),
            $minecraftVersion ?: 'any',
            $page,
            $pageSize,
            $sortField
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query, $minecraftVersion, $page, $pageSize, $sortField) {
            $response = $this->client()->get('/mods/search', [
                'gameId' => self::GAME_ID_MINECRAFT,
                'classId' => self::CLASS_ID_WORLDS,
                'searchFilter' => $query,
                'sortField' => $sortField,
                'sortOrder' => 'desc',
                'index' => max(0, ($page - 1) * $pageSize),
                'pageSize' => $pageSize,
                'gameVersion' => $minecraftVersion,
            ])->throw()->json();

            return [
                'data' => Arr::get($response, 'data', []),
                'pagination' => Arr::get($response, 'pagination', []),
            ];
        });
    }

    public function getFiles(int $projectId, ?string $minecraftVersion = null): array
    {
        $response = $this->client()->get("/mods/{$projectId}/files", [
            'gameVersion' => $minecraftVersion,
            'index' => 0,
            'pageSize' => 25,
        ])->throw()->json();

        return Arr::get($response, 'data', []);
    }

    public function getDownloadUrl(int $projectId, int $fileId): string
    {
        $response = $this->client()->get("/mods/{$projectId}/files/{$fileId}/download-url")
            ->throw()
            ->json();

        return (string) Arr::get($response, 'data');
    }

    private function client(): PendingRequest
    {
        return $this->http->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->timeout(20)
            ->withHeaders([
                'x-api-key' => (string) config('services.curseforge.api_key'),
            ]);
    }
}
