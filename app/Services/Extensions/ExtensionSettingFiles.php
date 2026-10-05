<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use DOMDocument;
use DOMElement;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Support\JsonValueGuard;

/**
 * Files uploaded through `file` extension settings. The type is decided from
 * the content (never from the client's name or MIME type), the stored name is
 * random and server-generated, and nothing path-like from the client is used.
 * Files live on a private panel disk under one directory per extension and are
 * only reachable through the fixed public route in routes/base.php.
 */
class ExtensionSettingFiles
{
    /** Every type a `file` setting may allow, mapped to its stored file extension. */
    public const array TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/x-icon' => 'ico',
        'image/svg+xml' => 'svg',
        'font/woff2' => 'woff2',
        'font/woff' => 'woff',
    ];

    /** The default allow-list: raster images only. SVG has to be listed explicitly. */
    public const array IMAGES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/avif'];

    public const string NAME_PATTERN = '/\A[a-f0-9]{40}\.(?:png|jpg|gif|webp|avif|ico|svg|woff2|woff)\z/';

    /** Hard ceiling for any `file` setting, whatever its definition declares. */
    public const int MAX_KILOBYTES = 10240;

    public const string ROUTE_PREFIX = 'extension-files';

    private const string DIRECTORY = 'extension-files';

    private const int MAX_DIMENSION = 10000;

    /** The public URL (a path on this panel) a stored file is served from. */
    public static function url(string $extension, string $name): string
    {
        return '/'.self::ROUTE_PREFIX.'/'.$extension.'/'.$name;
    }

    /**
     * Validate an upload against a definition's limits and store it under a
     * fresh random name.
     *
     * @param  list<string>  $mimes  allowed types, keys of self::TYPES
     * @return string the stored name, which is the setting's value
     */
    public function store(string $extension, UploadedFile $upload, array $mimes, int $maxKilobytes): string
    {
        $this->assertExtension($extension);
        $bytes = $upload->isValid() ? $upload->getContent() : '';
        if ($bytes === '') {
            $this->reject('The uploaded file is empty or could not be read.');
        }

        if (mb_strlen($bytes, '8bit') > min($maxKilobytes, self::MAX_KILOBYTES) * 1024) {
            $this->reject(sprintf('The file is larger than the allowed %d KB.', min($maxKilobytes, self::MAX_KILOBYTES)));
        }

        $mime = $this->detect($bytes);
        if ($mime === null || ! in_array($mime, $mimes, true)) {
            $this->reject(sprintf('Unsupported file type. Allowed: %s.', implode(', ', $mimes)));
        }

        $this->assertContent($mime, $bytes);

        $disk = $this->disk();
        do {
            $name = bin2hex(random_bytes(20)).'.'.self::TYPES[$mime];
        } while ($disk->exists($this->path($extension, $name)));

        if (! $disk->put($this->path($extension, $name), $bytes)) {
            $this->reject('The file could not be saved.');
        }

        return $name;
    }

    /**
     * The contents and served type of a stored file. The type comes from the
     * stored name, which was derived from the content at upload time.
     *
     * @return array{contents: string, mime: string}|null
     */
    public function read(string $extension, string $name): ?array
    {
        if (preg_match(ExtensionManifest::ID_REGEX, $extension) !== 1 || ExtensionSettingValueGuard::fileReference($name) === null) {
            return null;
        }

        $contents = $this->disk()->get($this->path($extension, $name));
        $mime = array_search(pathinfo($name, PATHINFO_EXTENSION), self::TYPES, true);

        return $contents === null || $mime === false ? null : ['contents' => $contents, 'mime' => $mime];
    }

    public function delete(string $extension, string $name): void
    {
        $this->assertExtension($extension);
        if (ExtensionSettingValueGuard::fileReference($name) !== null) {
            $this->disk()->delete($this->path($extension, $name));
        }
    }

    /**
     * Remove every file an extension uploaded, and the stored settings that
     * referenced them, so a later reinstall starts from its defaults.
     */
    public function purge(string $extension): void
    {
        $this->assertExtension($extension);
        $this->disk()->deleteDirectory(self::DIRECTORY.'/'.$extension);

        $references = [];
        foreach (ExtensionSetting::query()->where('extension', $extension)->where('is_secret', false)->whereNotNull('value')->get() as $row) {
            $encoded = $row->value;
            if ($encoded === null) {
                continue;
            }

            $value = rescue(fn (): bool|float|int|string|array|null => ExtensionSettingValueGuard::decode($encoded), report: false);
            if (ExtensionSettingValueGuard::fileReference($value) !== null) {
                $references[] = $row->id;
            }
        }

        if ($references !== []) {
            ExtensionSetting::query()->whereIn('id', $references)->delete();
        }
    }

    private function disk(): Filesystem
    {
        return Storage::disk(JsonValueGuard::string(config('extensions.files_disk')));
    }

    private function path(string $extension, string $name): string
    {
        return self::DIRECTORY.'/'.$extension.'/'.$name;
    }

    private function assertExtension(string $extension): void
    {
        throw_unless(preg_match(ExtensionManifest::ID_REGEX, $extension) === 1, ValidationException::withMessages(['file' => 'Invalid extension id.']));
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }

    /** The type of the bytes, from their signature alone. */
    private function detect(string $bytes): ?string
    {
        return match (true) {
            str_starts_with($bytes, "\x89PNG\r\n\x1a\n") => 'image/png',
            str_starts_with($bytes, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($bytes, 'GIF87a'), str_starts_with($bytes, 'GIF89a') => 'image/gif',
            str_starts_with($bytes, 'RIFF') && mb_substr($bytes, 8, 4, '8bit') === 'WEBP' => 'image/webp',
            mb_substr($bytes, 4, 4, '8bit') === 'ftyp' && str_contains(mb_substr($bytes, 8, 24, '8bit'), 'avif') => 'image/avif',
            str_starts_with($bytes, "\x00\x00\x01\x00") => 'image/x-icon',
            str_starts_with($bytes, 'wOF2') => 'font/woff2',
            str_starts_with($bytes, 'wOFF') => 'font/woff',
            $this->looksLikeSvg($bytes) => 'image/svg+xml',
            default => null,
        };
    }

    /** Checks beyond the signature: raster images must decode, SVG must be inert. */
    private function assertContent(string $mime, string $bytes): void
    {
        if ($mime === 'image/svg+xml') {
            $this->assertSafeSvg($bytes);

            return;
        }

        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            // Fonts carry no dimensions, and not every PHP build can read AVIF or ICO headers.
            return;
        }

        $size = @getimagesizefromstring($bytes);
        if ($size === false || $size['mime'] !== $mime) {
            $this->reject('The image is corrupt or unreadable.');
        }

        if ($size[0] < 1 || $size[1] < 1 || $size[0] > self::MAX_DIMENSION || $size[1] > self::MAX_DIMENSION) {
            $this->reject(sprintf('The image has unreasonable dimensions (max %d px per side).', self::MAX_DIMENSION));
        }
    }

    private function looksLikeSvg(string $bytes): bool
    {
        return preg_match('/\A(?:\xEF\xBB\xBF)?\s*(?:<\?xml[^>]*\?>\s*)?(?:<!--.*?-->\s*)*<svg[\s>]/is', mb_substr($bytes, 0, 2048, '8bit')) === 1;
    }

    /**
     * Reject SVGs carrying active or external content. The sandboxing policy the
     * file route serves them with is the real defence; this is a second layer.
     */
    private function assertSafeSvg(string $bytes): void
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY|<script|<foreignObject|<iframe|<embed|<object|<animate|<set\b|\son[a-z]+\s*=|javascript\s*:|data\s*:\s*text\/html|&#/i', $bytes) === 1) {
            $this->reject('The SVG contains active or external content and was rejected.');
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;
            $root = @$document->loadXML($bytes, LIBXML_NONET) ? $document->documentElement : null;
            if (! $root instanceof DOMElement || $root->localName !== 'svg') {
                $this->reject('The SVG is not valid XML.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
