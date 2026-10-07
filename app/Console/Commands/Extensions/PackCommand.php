<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use ZipArchive;

#[Description('Package the runtime files of a built extension into a .pteroext archive.')]
#[Signature('p:extension:pack {path : Path to the unpacked extension.} {--output= : Archive filename.} {--force : Overwrite an existing archive.}')]
class PackCommand extends Command
{
    public function handle(ExtensionManifestValidator $validator, ExtensionAssetPublisher $assets): int
    {
        try {
            $manifest = $validator->fromDirectory($this->argument('path'));
            throw_if($reason = $assets->unusableBuildReason($manifest), InvalidExtensionException::class, $reason);
            $output = $this->option('output') ?: getcwd().DIRECTORY_SEPARATOR.$manifest->id.'-'.$manifest->version.'.pteroext';
            throw_if(is_file($output) && ! $this->option('force'), InvalidExtensionException::class, 'Archive exists; pass --force to overwrite it.');
            File::ensureDirectoryExists(dirname($output));
            $temporary = dirname($output).DIRECTORY_SEPARATOR.'.extension-'.Str::uuid();
            $zip = new ZipArchive;
            $opened = false;
            try {
                throw_unless($zip->open($temporary, ZipArchive::CREATE | ZipArchive::EXCL) === true, InvalidExtensionException::class, 'Unable to open archive.');
                $opened = true;
                throw_unless($zip->addFile($manifest->path(ExtensionManifest::FILENAME), ExtensionManifest::FILENAME), InvalidExtensionException::class, 'Unable to add extension manifest to archive.');
                $directories = array_unique(['routes', 'database', 'resources', 'dist', 'vendor', ...array_values($manifest->autoload)]);
                foreach ($directories as $directory) {
                    if (! is_dir($manifest->path($directory))) {
                        continue;
                    }

                    foreach (File::allFiles($manifest->path($directory)) as $file) {
                        if ($file->isLink() || array_any(explode('/', str_replace('\\', '/', $file->getRelativePathname())), fn (string $part): bool => str_starts_with($part, '.') || in_array($part, ['node_modules', 'tests', 'test', 'coverage'], true))) {
                            continue;
                        }

                        $relative = $directory.'/'.$file->getRelativePathname();
                        throw_unless($zip->addFile($file->getPathname(), $relative), InvalidExtensionException::class, "Unable to add {$relative} to archive.");
                    }
                }

                $icon = $manifest->iconFile();
                if ($icon !== null && is_file($manifest->path($icon)) && $zip->locateName($icon) === false) {
                    throw_unless($zip->addFile($manifest->path($icon), $icon), InvalidExtensionException::class, "Unable to add {$icon} to archive.");
                }

                throw_unless($zip->close(), InvalidExtensionException::class, 'Unable to finish archive.');
                $opened = false;
                throw_unless(File::move($temporary, $output), InvalidExtensionException::class, 'Unable to save archive.');
            } finally {
                if ($opened) {
                    $zip->close();
                }

                File::delete($temporary);
            }
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Packaged {$manifest->id}: {$output}");

        return self::SUCCESS;
    }
}
