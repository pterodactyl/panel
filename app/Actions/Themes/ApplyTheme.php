<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Themes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Themes\AppliesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
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

        $stylesheet = $this->themes->publishedStylesheet();
        $publishedAssets = $this->themes->publishedAssetsDirectory();
        File::ensureDirectoryExists(dirname($stylesheet));
        $stagedTokens = dirname($stylesheet).'/.staging-'.Str::random(12);
        $stagedAssets = dirname($publishedAssets).'/.staging-'.Str::random(12);
        $assets = $theme['directory'].DIRECTORY_SEPARATOR.'assets';

        try {
            throw_unless(File::copy($tokens, $stagedTokens), InvalidExtensionException::class, 'Unable to stage theme stylesheet.');
            if (is_dir($assets)) {
                throw_unless(File::copyDirectory($assets, $stagedAssets), InvalidExtensionException::class, 'Unable to stage theme assets.');
            }

            $this->files->run([
                $stylesheet => $stagedTokens,
                $publishedAssets => is_dir($assets) ? $stagedAssets : null,
            ], static function (): void {});
        } finally {
            rescue(fn () => $this->files->delete($stagedTokens));
            rescue(fn () => $this->files->delete($stagedAssets));
        }
    }
}
