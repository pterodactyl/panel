<?php

namespace Pterodactyl\Traits\Helpers;

use Matriphe\ISO639\ISO639;
use Illuminate\Filesystem\Filesystem;

trait AvailableLanguages
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var \Matriphe\ISO639\ISO639
     */
    private $iso639;

    /**
     * Return all of the available languages on the Panel based on those
     * that are present in the language folder.
     *
     * @param bool $localize
     */
    public function getAvailableLanguages($localize = false): array
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
