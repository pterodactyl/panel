<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Pterodactyl\Support\JsonValueGuard;

/**
 * Installs theme.json + tokens.css themes and publishes the active one to public/assets/theme.css.
 */
class ThemeService
{
    public const string MANIFEST = 'theme.json';

    public function directory(): string
    {
        return mb_rtrim(JsonValueGuard::string(config('extensions.themes_directory', base_path('themes'))), '/\\');
    }

    public function publishedStylesheet(): string
    {
        return public_path('assets/theme.css');
    }

    public function publishedAssetsDirectory(): string
    {
        return public_path('assets/theme');
    }

    /**
     * All valid themes on disk, keyed by id.
     *
     * @return array<string, array{id: string, name: string, version: string, directory: string}>
     */
    public function discovered(): array
    {
        $themes = [];
        foreach (glob($this->directory().'/*/'.self::MANIFEST) ?: [] as $file) {
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }

            $data = json_decode($contents, true);
            if (! is_array($data)) {
                continue;
            }

            $id = $data['id'] ?? null;
            if (! is_string($id) || ! preg_match('/^[a-z][a-z0-9-]{0,47}$/', $id) || $id !== basename(dirname($file))) {
                continue;
            }

            $themes[$id] = [
                'id' => $id,
                'name' => is_string($data['name'] ?? null) ? $data['name'] : $id,
                'version' => is_string($data['version'] ?? null) ? $data['version'] : '0.0.0',
                'directory' => dirname($file),
            ];
        }

        return $themes;
    }
}
