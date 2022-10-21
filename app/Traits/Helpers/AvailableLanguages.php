<?php

namespace Pterodactyl\Traits\Helpers;

use Matriphe\ISO639\ISO639;
use Illuminate\Filesystem\Filesystem;

trait AvailableLanguages
{
    private ?ISO639 $iso639 = null;

    private ?Filesystem $filesystem = null;

    /**
     * Return all the available languages on the Panel based on those
     * that are present in the language folder.
     */
    public function getAvailableLanguages(bool $localize = false): array
    {
        return collect($this->getFilesystemInstance()->directories(resource_path('lang')))->mapWithKeys(function ($path) use ($localize) {
            $code = basename($path);
            $value = $localize ? $this->getIsoInstance()->nativeByCode1($code) : $this->getIsoInstance()->languageByCode1($code);

            return [$code => title_case($value)];
        })->toArray();
    }

    /**
     * Return an instance of the filesystem for getting a folder listing.
     */
    private function getFilesystemInstance(): Filesystem
    {
        return $this->filesystem = $this->filesystem ?: app()->make(Filesystem::class);
    }

    /**
     * Return an instance of the ISO639 class for generating names.
     */
    private function getIsoInstance(): ISO639
    {
        return $this->iso639 = $this->iso639 ?: app()->make(ISO639::class);
    }
}
