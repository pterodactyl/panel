<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Themes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Themes\AppliesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionDistFiles;
use Pterodactyl\Services\FilesystemChanges;
use Pterodactyl\Services\Themes\ThemeService;

final readonly class ApplyTheme implements AppliesThemes
{
    public function __construct(private ThemeService $themes, private FilesystemChanges $files) {}

    /** Publish a theme's tokens.css (and optional assets/ directory). */
    public function apply(string $id): void
    {
        $theme = $this->themes->discovered()[$id] ?? null;
        if ($theme === null) {
            throw new InvalidExtensionException("Theme \"{$id}\" was not found in {$this->themes->directory()}.");
        }

        $tokens = $theme['directory'].DIRECTORY_SEPARATOR.'tokens.css';
        throw_unless(is_file($tokens), InvalidExtensionException::class, "Theme \"{$id}\" has no tokens.css.");
        throw_if(is_link($tokens), InvalidExtensionException::class, "Theme \"{$id}\" ships tokens.css as a symbolic link; it must be a regular file.");

        // Published assets are web-served, so they get the same checks as an extension build.
        $assets = $theme['directory'].DIRECTORY_SEPARATOR.'assets';
        try {
            $files = is_dir($assets) ? ExtensionDistFiles::list($assets, 'assets') : null;
        } catch (InvalidExtensionException $invalidExtensionException) {
            throw new InvalidExtensionException("Theme \"{$id}\" {$invalidExtensionException->getMessage()}", $invalidExtensionException->getCode(), previous: $invalidExtensionException);
        }

        $stylesheet = $this->themes->publishedStylesheet();
        $publishedAssets = $this->themes->publishedAssetsDirectory();
        File::ensureDirectoryExists(dirname($stylesheet));
        $stagedTokens = dirname($stylesheet).'/.staging-'.Str::random(12);
        $stagedAssets = dirname($publishedAssets).'/.staging-'.Str::random(12);

        try {
            throw_unless(File::copy($tokens, $stagedTokens), InvalidExtensionException::class, 'Unable to stage theme stylesheet.');
            if ($files !== null) {
                // Copy exactly the files that were checked, never the directory itself.
                File::ensureDirectoryExists($stagedAssets);
                foreach ($files as $relative => $path) {
                    File::ensureDirectoryExists(dirname($stagedAssets.DIRECTORY_SEPARATOR.$relative));
                    throw_unless(File::copy($path, $stagedAssets.DIRECTORY_SEPARATOR.$relative), InvalidExtensionException::class, 'Unable to stage theme assets.');
                }
            }

            $this->files->run([
                $stylesheet => $stagedTokens,
                $publishedAssets => $files !== null ? $stagedAssets : null,
            ], static function (): void {});
        } finally {
            rescue(fn () => $this->files->delete($stagedTokens));
            rescue(fn () => $this->files->delete($stagedAssets));
        }
    }
}
