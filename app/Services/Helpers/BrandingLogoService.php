<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Helpers;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\Setting;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;

final class BrandingLogoService
{
    private const string SETTING_KEY = 'settings::app:logo';
    private const string FILE_GROUP = 'branding';
    private const int MAX_KILOBYTES = 10240;

    public function __construct(private readonly ExtensionSettingFiles $files) {}

    public function url(): ?string
    {
        $value = Setting::fetch(self::SETTING_KEY);

        return $this->fileName($value) === null ? null : $value;
    }

    public function replace(UploadedFile $upload): string
    {
        $previous = $this->fileName($this->url());
        $file = $this->files->store(
            self::FILE_GROUP,
            $upload,
            ['image/png', 'image/jpeg', 'image/webp', 'image/avif', 'image/x-icon', 'image/svg+xml'],
            self::MAX_KILOBYTES
        );
        $url = ExtensionSettingFiles::url(self::FILE_GROUP, $file);

        Setting::put(self::SETTING_KEY, $url);

        if ($previous !== null) {
            $this->files->delete(self::FILE_GROUP, $previous);
        }

        return $url;
    }

    public function clear(): void
    {
        $previous = $this->fileName($this->url());
        Setting::put(self::SETTING_KEY, null);

        if ($previous !== null) {
            $this->files->delete(self::FILE_GROUP, $previous);
        }
    }

    private function fileName(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $prefix = ExtensionSettingFiles::url(self::FILE_GROUP, '');
        if (! str_starts_with($url, $prefix)) {
            return null;
        }

        $file = substr($url, strlen($prefix));

        return preg_match(ExtensionSettingFiles::NAME_PATTERN, $file) === 1 ? $file : null;
    }
}
