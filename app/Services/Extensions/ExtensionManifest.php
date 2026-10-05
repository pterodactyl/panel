<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

/**
 * The parsed, validated extension.json of a single extension package.
 */
class ExtensionManifest
{
    public const string FILENAME = 'extension.json';

    public const string UI_ENTRY = 'dist/client.js';

    public const array COMPONENT_NAMES = [
        'dashboard.serverCard',
        'server.files.details',
        'server.files.editor',
        'server.files.manager',
    ];

    /** Lowercase slug: starts with a letter, then letters/digits/hyphens, max 48. */
    public const string ID_REGEX = '/^[a-z][a-z0-9-]{0,47}$/';

    /** A lucide icon name (kebab-case), such as "life-buoy" or "grid-2x2". */
    public const string ICON_REGEX = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/';

    /** Identifiers that would collide with core routes/assets. */
    public const array RESERVED_IDS = ['pterodactyl', 'panel', 'core'];

    /** Lowercase slug a top-level URL prefix claimed under `routes.root` must match. */
    public const string ROOT_PREFIX_REGEX = '/^[a-z][a-z0-9-]{0,47}$/';

    /**
     * Top-level URL segments an extension can never claim: the SPA's own client-side
     * routes (it is served from a catch-all, so they exist in no backend route), the
     * prefixes the catch-all hands to the backend, and well-known static paths. Segments
     * of registered core routes and existing public files are rejected on top of this.
     */
    public const array RESERVED_ROOT_PREFIXES = [
        'account', 'admin', 'api', 'assets', 'auth', 'daemon', 'extension-files', 'extensions',
        'favicons', 'locales', 'panel', 'sanctum', 'server', 'servers', 'storage', 'themes', 'up',
    ];

    /** The Tailwind prefix under `ui.prefix`: lowercase letters only, as Tailwind itself requires, with no trailing newline. */
    public const string UI_PREFIX_REGEX = '/^[a-z]{2,12}$/D';

    /**
     * Prefixes that would not keep an extension's styles apart from the panel's: Tailwind's
     * variants (`sm:flex` is already a panel class), its theme namespaces and the first
     * segment of the panel's own custom properties (`--color-*`, `--terminal-*`, `--tw-*`),
     * and names kept for the panel. `packages/sdk/prefix.spec.ts` derives the first three
     * from the installed Tailwind and the panel stylesheets and fails when one is missing.
     */
    public const array RESERVED_UI_PREFIXES = [
        'accent', 'active', 'after', 'animate', 'aria', 'aspect', 'autofill', 'backdrop', 'background',
        'before', 'blur', 'border', 'breakpoint', 'card', 'caution', 'chart', 'checked', 'color',
        'container', 'core', 'dark', 'data', 'default', 'destructive', 'disabled', 'drop', 'ease', 'editor',
        'empty', 'enabled', 'even', 'file', 'first', 'focus', 'font', 'foreground', 'group', 'has', 'hover',
        'in', 'inert', 'input', 'inset', 'invalid', 'landscape', 'last', 'layout', 'leading', 'lg', 'ltr',
        'marker', 'max', 'md', 'min', 'muted', 'noscript', 'not', 'nth', 'odd', 'only', 'open', 'optional',
        'panel', 'peer', 'perspective', 'placeholder', 'popover', 'portrait', 'primary', 'print', 'ptero',
        'radius', 'radix', 'required', 'ring', 'rtl', 'scrollbar', 'secondary', 'selection', 'shadow',
        'sidebar', 'sm', 'spacing', 'spinner', 'starting', 'state', 'success', 'sunken', 'supports', 'target',
        'terminal', 'text', 'theme', 'tracking', 'tw', 'valid', 'visited', 'warning', 'xl',
    ];

    private function __construct(
        public readonly string $directory,
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly ?string $description,
        public readonly ?string $author,
        public readonly ?string $provider,
        /** @var array<string, string> PSR-4 prefix => relative source directory */
        public readonly array $autoload,
        public readonly ?string $uiEntry,
        public readonly string $uiMode,
        /** @var list<ExtensionScreenDefinition> */
        public readonly array $screens,
        /** @var list<string> */
        public readonly array $components,
        /** @var array<'panel'|'sdk'|'php', string> */
        public readonly array $requirements,
        /** @var array<string, string> */
        public readonly array $requiredExtensions,
        /** @var list<string> top-level URL prefixes claimed under `routes.root` */
        public readonly array $rootPrefixes = [],
        /** The Tailwind prefix this extension builds its utilities and theme variables with. */
        public readonly ?string $uiPrefix = null,
    ) {}

    /**
     * @param  ExtensionManifestInput  $data
     * @param  array<string, string>  $autoload
     */
    public static function fromValidatedData(
        string $directory,
        array $data,
        array $autoload,
        ?string $uiEntry,
        string $uiMode,
        ?string $provider,
    ): self {
        return new self(
            directory: mb_rtrim($directory, '/\\'),
            id: $data['id'],
            name: $data['name'],
            version: $data['version'],
            description: is_string($data['description'] ?? null) ? $data['description'] : null,
            author: is_string($data['author'] ?? null) ? $data['author'] : null,
            provider: $provider,
            autoload: $autoload,
            uiEntry: $uiEntry,
            uiMode: $uiMode,
            screens: $data['ui']['screens'] ?? [],
            components: $data['ui']['components'] ?? [],
            requirements: array_intersect_key($data['requires'] ?? [], ['panel' => true, 'sdk' => true, 'php' => true]),
            requiredExtensions: $data['requires']['extensions'] ?? [],
            rootPrefixes: $data['routes']['root'] ?? [],
            uiPrefix: $data['ui']['prefix'] ?? null,
        );
    }

    /** Whether a Tailwind prefix is well formed and not reserved; uniqueness is compared when an extension is enabled. */
    public static function isUsableUiPrefix(string $prefix): bool
    {
        return preg_match(self::UI_PREFIX_REGEX, $prefix) === 1 && ! in_array($prefix, self::RESERVED_UI_PREFIXES, true);
    }

    /**
     * The prefix suggested for an extension id: the initials of its words (`hello-world`
     * gives `hw`), else its letters, else its letters followed by `ui` when both are
     * too short or reserved.
     */
    public static function defaultUiPrefix(string $id): string
    {
        $words = [];
        foreach (explode('-', $id) as $word) {
            $word = implode('', array_filter(mb_str_split($word), ctype_lower(...)));
            if ($word !== '') {
                $words[] = $word;
            }
        }

        $letters = implode('', $words);
        $fallback = mb_substr($letters, 0, 10).'ui';
        $initials = implode('', array_map(fn (string $word): string => mb_substr($word, 0, 1), $words));

        return array_find([mb_substr($initials, 0, 12), mb_substr($letters, 0, 12)], self::isUsableUiPrefix(...)) ?? $fallback;
    }

    public function hasUi(): bool
    {
        return $this->uiEntry !== null;
    }

    public function path(string ...$parts): string
    {
        return $this->directory.($parts === [] ? '' : DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts));
    }
}
