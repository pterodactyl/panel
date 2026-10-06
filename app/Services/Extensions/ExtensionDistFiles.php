<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Symfony\Component\Finder\Finder;

/**
 * The files of an extension build that may be published under the web root. dist is served
 * as-is, so it is walked with none of Finder's default ignore rules (which skip `CVS/`, `.git/`
 * and dotfiles) and anything a web server could treat as more than a static asset fails the build.
 */
final class ExtensionDistFiles
{
    /** @var list<string> */
    public const array EXTENSIONS = [
        'js', 'mjs', 'css', 'map', 'json', 'wasm', 'txt',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg', 'ico',
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        'mp3', 'ogg', 'wav', 'mp4', 'webm',
    ];

    /**
     * @return array<string, string> absolute paths keyed by their path relative to dist, sorted
     *
     * @throws InvalidExtensionException
     */
    public static function list(string $dist): array
    {
        $files = [];
        // Directories are listed too, so a linked directory is refused rather than silently skipped.
        $finder = Finder::create()->in($dist)->ignoreVCS(false)->ignoreDotFiles(false);

        foreach ($finder as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $problem = match (true) {
                $file->isLink() => 'a symbolic link',
                preg_match('~(^|/)\.~', $relative) === 1 => 'a dotfile',
                $file->isDir() => null,
                ! $file->isFile() => 'not a regular file',
                ! in_array(mb_strtolower($file->getExtension()), self::EXTENSIONS, true) => 'not a browser asset',
                default => null,
            };
            throw_if($problem !== null, InvalidExtensionException::class, "ships dist/{$relative}, which is {$problem}; dist may only contain browser assets.");

            if ($file->isFile()) {
                $files[$relative] = $file->getPathname();
            }
        }

        ksort($files, SORT_STRING);

        return $files;
    }
}
