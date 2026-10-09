<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Helpers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Pterodactyl\Exceptions\ManifestDoesNotExistException;
use Pterodactyl\Support\JsonValueGuard;

class AssetHashService
{
    public const string MANIFEST_PATH = 'assets/manifest.json';

    public const string MAIN_ENTRY = 'resources/scripts/index.tsx';

    /**
     * Bare module specifiers shared with runtime-loaded extension bundles, mapped to
     * the source keys of the runtime entries in Vite's native manifest. The
     * import map built from these lets extension bundles resolve "react" etc. to the
     * exact module instances the panel bundle itself uses.
     */
    public const array EXTENSION_RUNTIME_IMPORTS = [
        'react' => 'resources/scripts/ext-runtime/react.ts',
        'react/jsx-runtime' => 'resources/scripts/ext-runtime/react-jsx-runtime.ts',
        'react-dom' => 'resources/scripts/ext-runtime/react-dom.ts',
        'react-dom/client' => 'resources/scripts/ext-runtime/react-dom-client.ts',
        '@tanstack/react-query' => 'resources/scripts/ext-runtime/tanstack-react-query.ts',
        '@pterodactyl/sdk' => 'resources/scripts/sdk/index.ts',
        '@pterodactyl/sdk/api' => 'resources/scripts/sdk/api.ts',
    ];

    /** @var ViteManifest|null */
    protected static ?array $manifest = null;

    private readonly Filesystem $filesystem;

    /**
     * AssetHashService constructor.
     *
     * @param  string|null  $publicPath  Directory holding the built assets; defaults to the public path.
     */
    public function __construct(FilesystemManager $filesystem, ?string $publicPath = null)
    {
        $this->filesystem = $filesystem->createLocalDriver(['root' => $publicPath ?? public_path()]);
    }

    /**
     * Modify a URL to append the asset hash.
     */
    public function url(string $resource): string
    {
        $entry = $this->entry($resource);

        return $entry === null ? $resource : $this->assetUrl($entry['file']);
    }

    /**
     * Return the data integrity hash for a resource.
     */
    public function integrity(string $resource): string
    {
        return $this->entry($resource)['integrity'] ?? '';
    }

    /**
     * Return stylesheet tags for CSS emitted with a JS entry.
     */
    public function cssImports(string $resource): string
    {
        $entry = $this->entry($resource);
        if ($entry === null) {
            return '';
        }

        $tags = [];
        foreach ($entry['css'] ?? [] as $file) {
            if ($file === '') {
                continue;
            }

            $tags[] = $this->stylesheetTag(
                $this->assetUrl($file),
                $entry['cssIntegrity'][$file] ?? ''
            );
        }

        return implode('', $tags);
    }

    /**
     * Return a built JS import using the provided URL.
     */
    public function js(string $resource): string
    {
        $attributes = [
            'src' => $this->url($resource),
            'crossorigin' => 'anonymous',
            // Vite emits ES modules (required for code-splitting / dynamic import),
            // so the entry script must be loaded as a module.
            'type' => 'module',
        ];

        if (config('pterodactyl.assets.use_hash')) {
            $attributes['integrity'] = $this->integrity($resource);
        }

        $output = '<script';
        foreach ($attributes as $key => $value) {
            $output .= " $key=\"$value\"";
        }

        return $output.'></script>';
    }

    /**
     * Build the <script type="importmap"> tag resolving the shared extension-runtime
     * specifiers. Must be emitted before the main module script. Returns an empty
     * string when the manifest predates the extension runtime (older builds).
     */
    public function importMap(): string
    {
        $imports = [];
        $integrity = [];

        foreach (self::EXTENSION_RUNTIME_IMPORTS as $specifier => $source) {
            $entry = $this->entry($source);
            if ($entry === null) {
                continue;
            }

            $url = $this->assetUrl($entry['file']);
            $imports[$specifier] = $url;
            if (! empty($entry['integrity'])) {
                $integrity[$url] = $entry['integrity'];
            }
        }

        if ($imports === []) {
            return '';
        }

        $map = ['imports' => $imports];
        if (config('pterodactyl.assets.use_hash') && $integrity !== []) {
            $map['integrity'] = $integrity;
        }

        return '<script type="importmap">'.json_encode($map, JSON_UNESCAPED_SLASHES).'</script>';
    }

    /**
     * Get the asset manifest and store it in the cache for quicker lookups.
     *
     * @return ViteManifest Vite's manifest, keyed by source path.
     */
    protected function manifest(): array
    {
        if (static::$manifest === null) {
            $contents = $this->filesystem->get(self::MANIFEST_PATH);
            throw_if($contents === null, ManifestDoesNotExistException::class);

            self::$manifest = $this->parseManifest($contents);
        }

        $manifest = static::$manifest;
        throw_if($manifest === null, ManifestDoesNotExistException::class);

        return $manifest;
    }

    private function stylesheetTag(string $href, string $integrity = ''): string
    {
        $attributes = [
            'href' => $href,
            'rel' => 'stylesheet',
            'crossorigin' => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ];

        if (config('pterodactyl.assets.use_hash') && $integrity !== '') {
            $attributes['integrity'] = $integrity;
        }

        $output = '<link';
        foreach ($attributes as $key => $value) {
            $output .= " $key=\"$value\"";
        }

        return $output.'>';
    }

    /** @return ViteManifestEntry|null */
    private function entry(string $resource): ?array
    {
        $key = $resource === 'main.js' ? self::MAIN_ENTRY : $resource;

        return $this->manifest()[$key] ?? null;
    }

    private function assetUrl(string $file): string
    {
        return '/assets/'.mb_ltrim($file, '/');
    }

    /** @return ViteManifest */
    private function parseManifest(string $contents): array
    {
        $decoded = JsonValueGuard::decodeArray8($contents);
        $manifest = [];

        foreach ($decoded as $source => $value) {
            if (! is_string($source) || ! is_array($value) || ! is_string($value['file'] ?? null)) {
                continue;
            }

            $entry = ['file' => $value['file']];
            if (is_string($value['integrity'] ?? null)) {
                $entry['integrity'] = $value['integrity'];
            }

            $css = $value['css'] ?? null;
            if (is_array($css) && array_is_list($css)) {
                $files = [];
                foreach ($css as $file) {
                    if (is_string($file)) {
                        $files[] = $file;
                    }
                }

                $entry['css'] = $files;
            }

            $cssIntegrity = $value['cssIntegrity'] ?? null;
            if (is_array($cssIntegrity)) {
                $hashes = [];
                foreach ($cssIntegrity as $file => $hash) {
                    if (is_string($file) && is_string($hash)) {
                        $hashes[$file] = $hash;
                    }
                }

                $entry['cssIntegrity'] = $hashes;
            }

            $manifest[$source] = $entry;
        }

        return $manifest;
    }
}
