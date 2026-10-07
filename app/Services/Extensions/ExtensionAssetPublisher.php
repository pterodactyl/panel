<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

class ExtensionAssetPublisher
{
    public const int ICON_MAX_KILOBYTES = 512;

    public const int ICON_MAX_DIMENSION = 2048;

    private const array ICON_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    public function __construct(private readonly ExtensionStylesheetInspector $stylesheets) {}

    public function iconUrl(ExtensionManifest $manifest): ?string
    {
        $icon = $this->icon($manifest);

        return $icon === null ? null : sprintf('/api/admin/extensions/%s/icon?v=%s', $manifest->id, hash('xxh3', $icon['contents']));
    }

    /** @return array{contents: string, mime: string}|null */
    public function icon(ExtensionManifest $manifest): ?array
    {
        $path = $this->iconPath($manifest);
        if ($path === null) {
            return null;
        }

        $contents = File::get($path);
        $size = @getimagesizefromstring($contents);
        if ($size === false || ! in_array($size['mime'], self::ICON_TYPES, true)) {
            return null;
        }

        if ($size[0] < 1 || $size[1] < 1 || $size[0] > self::ICON_MAX_DIMENSION || $size[1] > self::ICON_MAX_DIMENSION) {
            return null;
        }

        return ['contents' => $contents, 'mime' => $size['mime']];
    }

    public function iconProblem(ExtensionManifest $manifest): ?string
    {
        $file = $manifest->iconFile();
        if ($file === null) {
            return null;
        }

        return match (true) {
            $this->iconPath($manifest) === null => sprintf('Icon "%s" is missing, outside the package or larger than %d KB.', $file, self::ICON_MAX_KILOBYTES),
            $this->icon($manifest) === null => sprintf('Icon "%s" is not a readable PNG, JPEG or WebP image of at most %d px per side.', $file, self::ICON_MAX_DIMENSION),
            default => null,
        };
    }

    public function entryUrl(ExtensionManifest $manifest): string
    {
        $version = $this->currentVersion($manifest->id);
        if ($version !== null) {
            return sprintf('/assets/extensions/%s/%s/client.js', $manifest->id, $version);
        }

        return sprintf('/assets/extensions/%s/client.js?v=%s', $manifest->id, Str::slug($manifest->version));
    }

    public function publish(ExtensionManifest $manifest): void
    {
        $version = $this->stage($manifest);
        if ($version !== null) {
            $this->activate($manifest->id, $version);
        }
    }

    /**
     * Publish the build, then run the callback. If either fails, `_current` goes back to the
     * previous build and the build directory this call created is removed, so nothing from a
     * failed install or enable stays web-served.
     *
     * @param  Closure(): void  $callback
     */
    public function publishWith(ExtensionManifest $manifest, Closure $callback): void
    {
        $previous = $this->currentVersion($manifest->id);
        $existing = $this->builds($manifest->id);

        try {
            $this->publish($manifest);
            $callback();
        } catch (Throwable $throwable) {
            rescue(fn () => $this->activate($manifest->id, $previous));
            $current = $this->currentVersion($manifest->id);
            foreach (array_diff($this->builds($manifest->id), $existing) as $build) {
                if (basename($build) !== $current) {
                    rescue(fn (): bool => File::deleteDirectory($build));
                }
            }

            throw $throwable;
        }
    }

    public function stage(ExtensionManifest $manifest): ?string
    {
        if (! $manifest->hasUi()) {
            return null;
        }

        throw_if($reason = $this->unusableBuildReason($manifest), InvalidExtensionException::class, $reason);
        $files = ExtensionDistFiles::list($manifest->path('dist'));
        $hash = hash_init('sha256');
        foreach ($files as $relative => $path) {
            hash_update($hash, $relative."\0");
            hash_update_file($hash, $path);
        }

        $version = hash_final($hash);
        $root = $this->publishedPath($manifest->id);
        $target = $root.DIRECTORY_SEPARATOR.$version;
        if (! is_dir($target)) {
            $staged = $root.DIRECTORY_SEPARATOR.'.staging-'.Str::random(12);
            File::ensureDirectoryExists($root);
            try {
                // Copy exactly the files that were checked, never the directory itself.
                foreach ($files as $relative => $path) {
                    File::ensureDirectoryExists(dirname($staged.DIRECTORY_SEPARATOR.$relative));
                    throw_unless(File::copy($path, $staged.DIRECTORY_SEPARATOR.$relative), InvalidExtensionException::class, 'Unable to stage extension assets.');
                }

                throw_unless(File::moveDirectory($staged, $target), InvalidExtensionException::class, 'Unable to publish extension assets.');
            } finally {
                File::deleteDirectory($staged);
            }
        }

        return $version;
    }

    public function currentVersion(string $identifier): ?string
    {
        $path = $this->publishedPath($identifier).DIRECTORY_SEPARATOR.'_current';
        if (! is_file($path)) {
            return null;
        }

        $version = mb_trim(File::get($path));

        return preg_match('/^[a-f0-9]{64}$/', $version) ? $version : null;
    }

    public function activate(string $identifier, ?string $version): void
    {
        $path = $this->publishedPath($identifier).DIRECTORY_SEPARATOR.'_current';
        if ($version === null) {
            File::delete($path);
            $this->stopDevelopment($identifier);
        } else {
            File::replace($path, $version);
            if (is_file($this->developmentPath($identifier))) {
                File::replace($this->developmentPath($identifier), $version);
            }
        }
    }

    public function missingBuildReason(ExtensionManifest $manifest): ?string
    {
        if (! $manifest->hasUi()) {
            return null;
        }

        if (! is_file($manifest->path($manifest->uiEntry ?? ExtensionManifest::UI_ENTRY))) {
            return "Extension \"{$manifest->id}\" declares ui.entry \"{$manifest->uiEntry}\" but the built file is missing - build the extension before installing.";
        }

        return null;
    }

    /** Why the built frontend cannot be published: it is missing, or its styles would override the panel's or another extension's. */
    public function unusableBuildReason(ExtensionManifest $manifest): ?string
    {
        if (! $manifest->hasUi()) {
            return null;
        }

        return $this->missingBuildReason($manifest) ?? $this->distFilesReason($manifest) ?? $this->stylesheets->conflictReason($manifest);
    }

    public function publishedPath(string $identifier): string
    {
        return mb_rtrim(JsonValueGuard::string(config('extensions.assets_directory')), '/\\').DIRECTORY_SEPARATOR.$identifier;
    }

    public function watchDevelopment(string $identifier): void
    {
        $version = $this->currentVersion($identifier);
        throw_if($version === null, InvalidExtensionException::class, 'Publish a frontend build before watching it.');
        File::replace($this->developmentPath($identifier), $version);
    }

    public function stopDevelopment(string $identifier): void
    {
        File::delete($this->developmentPath($identifier));
    }

    /** @return array{url: string, version: string}|null */
    public function developmentPayload(string $identifier): ?array
    {
        $version = $this->currentVersion($identifier);
        if (! config('app.debug') || $version === null || ! is_file($this->developmentPath($identifier))) {
            return null;
        }

        return ['url' => '/assets/extensions/'.$identifier.'/_development', 'version' => $version];
    }

    /** Published assets are web-served, so dist may only hold static browser assets. */
    private function distFilesReason(ExtensionManifest $manifest): ?string
    {
        try {
            ExtensionDistFiles::list($manifest->path('dist'));
        } catch (InvalidExtensionException $invalidExtensionException) {
            return "Extension \"{$manifest->id}\" {$invalidExtensionException->getMessage()}";
        }

        return null;
    }

    /** @return list<string> the published build directories, never the dot-prefixed staging ones */
    private function builds(string $identifier): array
    {
        return glob($this->publishedPath($identifier).DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [];
    }

    private function iconPath(ExtensionManifest $manifest): ?string
    {
        $file = $manifest->iconFile();
        if ($file === null) {
            return null;
        }

        $root = realpath($manifest->directory);
        $path = realpath($manifest->path(...explode('/', $file)));
        if ($root === false || $path === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            return null;
        }

        $bytes = filesize($path);

        return $bytes !== false && $bytes > 0 && $bytes <= self::ICON_MAX_KILOBYTES * 1024 ? $path : null;
    }

    private function developmentPath(string $identifier): string
    {
        return $this->publishedPath($identifier).DIRECTORY_SEPARATOR.'_development';
    }
}
