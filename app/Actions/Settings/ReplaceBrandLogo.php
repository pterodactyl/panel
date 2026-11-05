<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Settings;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Contracts\Settings\ReplacesBrandLogo;
use Pterodactyl\Models\Setting;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;
use Throwable;

final readonly class ReplaceBrandLogo implements ReplacesBrandLogo
{
    /** Logos are stored with extension setting files under this id, which no extension may take. */
    public const string FILES = 'panel-logo';

    private const array TYPES = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp', 'image/avif', 'image/x-icon'];

    public function __construct(private ExtensionSettingFiles $files) {}

    /**
     * The outer bound of an upload; its type and size are checked against its content when it is stored.
     *
     * @return ValidationRules
     */
    public static function rules(): array
    {
        return ['logo' => ['required', 'file', 'max:'.ExtensionSettingFiles::MAX_KILOBYTES]];
    }

    /**
     * Where the logo is served from; null while the panel has none. SettingsServiceProvider
     * loads the stored file name into `app.logo`.
     */
    public static function url(): ?string
    {
        $name = self::current();

        return $name === null ? null : ExtensionSettingFiles::url(self::FILES, $name);
    }

    public function replace(?UploadedFile $logo): ?string
    {
        $previous = self::current();
        $name = $logo instanceof UploadedFile ? $this->files->store(self::FILES, $logo, self::TYPES, ExtensionSettingFiles::MAX_KILOBYTES) : null;

        try {
            Setting::put('settings::app:logo', $name);
        } catch (Throwable $throwable) {
            if ($name !== null) {
                rescue(fn () => $this->files->delete(self::FILES, $name));
            }

            throw $throwable;
        }

        config(['app.logo' => $name ?? '']);

        if ($previous !== null) {
            rescue(fn () => $this->files->delete(self::FILES, $previous));
        }

        return self::url();
    }

    private static function current(): ?string
    {
        return ExtensionSettingValueGuard::fileReference(config()->string('app.logo'));
    }
}
