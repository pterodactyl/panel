<?php

declare(strict_types=1);

namespace Pterodactyl\Traits\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Locale;

trait AvailableLanguages
{
    /**
     * Return all the available languages on the Panel based on those
     * that are present in the language folder.
     *
     * @return array<string, string> language code mapped to its display name
     */
    public function getAvailableLanguages(bool $localize = false): array
    {
        $languages = [];
        foreach (array_filter(File::directories(resource_path('lang')), is_string(...)) as $path) {
            $code = basename($path);
            $value = Locale::getDisplayLanguage($code, $localize ? $code : 'en') ?: $code;
            $languages[$code] = Str::title($value);
        }

        return $languages;
    }
}
