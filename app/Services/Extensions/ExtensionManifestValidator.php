<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use JsonException;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Themes\ThemeDefinition;
use Pterodactyl\Services\Themes\ThemeRepository;
use Pterodactyl\Services\Themes\ThemeSetting;
use ReflectionClass;
use UnexpectedValueException;

class ExtensionManifestValidator
{
    /** @var list<string>|null */
    private ?array $panelNamespaces = null;

    public function fromDirectory(string $directory): ExtensionManifest
    {
        $path = mb_rtrim($directory, '/\\').DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME;
        throw_unless(is_file($path), InvalidExtensionException::class, 'No '.ExtensionManifest::FILENAME." found in {$directory}.");

        try {
            $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidExtensionException("{$path} is not valid JSON.");
        }

        $this->validateManifestStructure($data);

        $parser = new VersionParser;
        try {
            $parser->normalize($data['version']);
            foreach ([$data['requires']['panel'] ?? null, $data['requires']['sdk'] ?? null, $data['requires']['php'] ?? null, ...array_values($data['requires']['extensions'] ?? [])] as $constraint) {
                if ($constraint !== null) {
                    $parser->parseConstraints($constraint);
                }
            }
        } catch (UnexpectedValueException $unexpectedValueException) {
            throw new InvalidExtensionException('Invalid extension version or requirement: '.$unexpectedValueException->getMessage(), $unexpectedValueException->getCode(), $unexpectedValueException);
        }

        foreach (array_keys($data['requires']['extensions'] ?? []) as $dependency) {
            throw_unless(preg_match(ExtensionManifest::ID_REGEX, $dependency) && $dependency !== $data['id'], InvalidExtensionException::class, 'Extension dependencies must be valid identifiers other than the extension itself.');
        }

        foreach ($data['ui']['screens'] ?? [] as $screen) {
            $parameters = array_values(array_filter(explode('/', $screen['path']), fn (string $segment): bool => str_starts_with($segment, '$')));
            throw_if(in_array('$id', $parameters, true), InvalidExtensionException::class, 'The id path parameter is reserved for the panel.');
            throw_if(count(array_unique($parameters)) !== count($parameters), InvalidExtensionException::class, 'Screen path parameters must be unique.');
            if (isset($screen['nav'])) {
                $names = array_map(fn (string $parameter): string => mb_substr($parameter, 1), $parameters);
                $defaults = array_keys($screen['nav']['params'] ?? []);
                throw_if(array_diff($names, $defaults) !== [] || array_diff($defaults, $names) !== [], InvalidExtensionException::class, 'Navigation params must supply exactly the screen path parameters.');
            }

            if (isset($screen['when'])) {
                $this->validateScreenCondition($screen['when']);
            }
        }

        $this->assertRootPrefixesAreFree($data['routes']['root'] ?? []);

        $id = $data['id'];
        throw_unless(preg_match(ExtensionManifest::ID_REGEX, $id), InvalidExtensionException::class, "Extension id \"{$id}\" must match ".ExtensionManifest::ID_REGEX.'.');

        throw_if(in_array($id, ExtensionManifest::RESERVED_IDS, true), InvalidExtensionException::class, "Extension id \"{$id}\" is reserved.");

        $autoload = isset($data['autoload'])
            ? $this->normalizeAutoload($data['autoload'], 'Manifest "autoload"')
            : $this->normalizeAutoload($this->composerAutoload($directory), 'composer.json "autoload.psr-4"');
        [$uiEntry, $uiMode] = $this->normalizeUi($data['ui'] ?? null);

        $provider = $data['provider'] ?? null;
        throw_if($provider !== null && $provider === '', InvalidExtensionException::class, 'Manifest "provider" must be a class name string.');

        $icon = $data['icon'] ?? null;
        throw_if($icon !== null && preg_match(ExtensionManifest::ICON_REGEX, $icon) !== 1 && preg_match(ExtensionManifest::ICON_FILE_REGEX, $icon) !== 1, InvalidExtensionException::class, 'Manifest "icon" must be a lucide icon name such as "life-buoy", or the relative path of a .png, .jpg or .webp image inside the package.');

        if (($data['type'] ?? null) === 'theme') {
            $this->validateTheme($id, $data['theme'] ?? []);
        }

        return ExtensionManifest::fromValidatedData(
            directory: $directory,
            data: $data,
            autoload: $autoload,
            uiEntry: $uiEntry,
            uiMode: $uiMode,
            provider: $provider,
        );
    }

    /**
     * @phpstan-assert ExtensionManifestInput $data
     */
    private function validateManifestStructure(mixed $data): void
    {
        throw_unless(is_array($data), InvalidExtensionException::class, 'The extension manifest must be a JSON object.');

        $validator = Validator::make($data, [
            'id' => ['required', 'string'],
            'name' => ['required', 'string'],
            'version' => ['required', 'string', 'max:64'],
            'type' => ['sometimes', 'string', Rule::in(ExtensionManifest::TYPES)],
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string'],
            'provider' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'autoload' => ['sometimes', 'array'],
            'requires' => ['sometimes', 'array:panel,sdk,php,extensions'],
            'requires.panel' => ['sometimes', 'string', 'max:255'],
            'requires.sdk' => ['required_with:ui.views', 'string', 'max:255'],
            'requires.php' => ['sometimes', 'string', 'max:255'],
            'requires.extensions' => ['sometimes', 'array', 'max:64'],
            'requires.extensions.*' => ['required', 'string', 'max:255'],
            'ui' => ['sometimes', 'array'],
            'ui.entry' => ['required_with:ui', 'string'],
            'ui.mode' => ['sometimes', 'string'],
            'ui.prefix' => ['sometimes', 'prohibited_if:type,theme', 'filled', 'string', 'regex:'.ExtensionManifest::UI_PREFIX_REGEX, Rule::notIn(ExtensionManifest::RESERVED_UI_PREFIXES)],
            'ui.views' => ['sometimes', 'prohibited_if:type,theme', 'array', 'list'],
            'ui.views.*' => ['required', 'string', 'distinct:strict', 'regex:'.ExtensionManifest::VIEW_NAME_REGEX],
            'ui.screens' => ['sometimes', 'array', 'list', 'max:64'],
            'ui.screens.*' => ['required', 'array:id,area,path,nav,permission,parent,when'],
            'ui.screens.*.id' => ['required', 'string', 'max:48', 'regex:/^[a-z][a-z0-9-]*$/D', 'distinct:strict'],
            'ui.screens.*.area' => ['required', 'string', 'in:account,server,admin'],
            'ui.screens.*.path' => ['required', 'string', 'max:255', 'regex:~^[a-z][a-z0-9-]*(?:/(?:[a-z][a-z0-9-]*|\\$[a-zA-Z][a-zA-Z0-9_]*))*/?$~D'],
            'ui.screens.*.parent' => ['sometimes', 'string', 'prohibited_unless:ui.screens.*.area,admin', 'in:admin.node,admin.server,admin.egg,admin.user'],
            'ui.screens.*.nav' => ['sometimes', 'array:label,exact,params,order,group,badge,icon', 'required_array_keys:label'],
            'ui.screens.*.nav.label' => ['required_with:ui.screens.*.nav', 'string', 'max:100'],
            'ui.screens.*.nav.exact' => ['sometimes', 'boolean:strict'],
            'ui.screens.*.nav.params' => ['sometimes', 'array'],
            'ui.screens.*.nav.params.*' => ['required', 'string', 'max:255'],
            'ui.screens.*.nav.order' => ['sometimes', 'integer', 'between:-10000,10000'],
            'ui.screens.*.nav.group' => ['sometimes', 'string', 'max:100'],
            'ui.screens.*.nav.badge' => ['sometimes', 'string', 'max:32'],
            'ui.screens.*.nav.icon' => ['sometimes', 'string', 'max:64', 'regex:'.ExtensionManifest::ICON_REGEX],
            'ui.screens.*.when' => ['sometimes', 'array:eggFeatures,eggTags,match,runtime'],
            'ui.screens.*.when.eggFeatures' => ['sometimes', 'prohibited_unless:ui.screens.*.area,server', 'array:any,all'],
            'ui.screens.*.when.eggFeatures.*' => ['required', 'array', 'list', 'min:1', 'max:32'],
            'ui.screens.*.when.eggFeatures.*.*' => ['required', 'string', 'max:100'],
            'ui.screens.*.when.eggTags' => ['sometimes', 'prohibited_unless:ui.screens.*.area,server', 'array:any,all'],
            'ui.screens.*.when.eggTags.*' => ['required', 'array', 'list', 'min:1', 'max:32'],
            'ui.screens.*.when.eggTags.*.*' => ['required', 'string', 'max:100'],
            'ui.screens.*.when.match' => ['sometimes', 'string', 'in:all,any'],
            'ui.screens.*.when.runtime' => ['sometimes', 'boolean:strict'],
            'ui.screens.*.permission' => ['sometimes', 'prohibited_unless:ui.screens.*.area,server', 'array', 'list'],
            'ui.screens.*.permission.*' => ['required', 'string', 'max:100'],
            'routes' => ['sometimes', 'array:root'],
            'routes.root' => ['sometimes', 'array', 'list', 'max:8'],
            'routes.root.*' => ['required', 'string', 'distinct:strict', 'regex:'.ExtensionManifest::ROOT_PREFIX_REGEX],
            'theme' => ['sometimes', 'prohibited_unless:type,theme', 'array:extends,stylesheet,schemes,settings'],
            'theme.extends' => ['sometimes', 'string', 'regex:'.ExtensionManifest::ID_REGEX],
            'theme.stylesheet' => ['sometimes', 'nullable', 'string', Rule::in([ThemeDefinition::STYLESHEET])],
            'theme.schemes' => ['sometimes', 'array', 'list', 'min:1', 'max:2'],
            'theme.schemes.*' => ['required', 'string', 'distinct:strict', Rule::in(ThemeDefinition::SCHEMES)],
            'theme.settings' => ['sometimes', 'array', $this->keyedBy(ThemeSetting::KEY_REGEX, 'Theme setting names must match '.ThemeSetting::KEY_REGEX.'.')],
            'theme.settings.*' => ['required', 'array:type,label,default,help,token,attribute,options,min,max,step,unit,maxLength'],
            'theme.settings.*.type' => ['required', 'string', Rule::in(ThemeSetting::TYPES)],
            'theme.settings.*.label' => ['required', 'string'],
            'theme.settings.*.help' => ['sometimes', 'nullable', 'string'],
            'theme.settings.*.token' => ['sometimes', 'nullable', 'string', 'regex:'.ThemeSetting::TOKEN_REGEX],
            'theme.settings.*.attribute' => ['sometimes', 'nullable', 'string', 'regex:'.ThemeSetting::ATTRIBUTE_REGEX, Rule::notIn(ThemeSetting::RESERVED_ATTRIBUTES)],
            'theme.settings.*.options' => ['required_if:theme.settings.*.type,select', 'prohibited_unless:theme.settings.*.type,select', 'array', 'min:1', $this->keyedBy(ThemeSetting::OPTION_REGEX, 'Theme setting options must be keyed by identifiers such as "side".')],
            'theme.settings.*.options.*' => ['required', 'string'],
            'theme.settings.*.min' => ['sometimes', 'prohibited_unless:theme.settings.*.type,number', 'numeric'],
            'theme.settings.*.max' => ['sometimes', 'prohibited_unless:theme.settings.*.type,number', 'numeric'],
            'theme.settings.*.step' => ['sometimes', 'prohibited_unless:theme.settings.*.type,number', 'numeric', 'gt:0'],
            'theme.settings.*.unit' => ['sometimes', 'prohibited_unless:theme.settings.*.type,number', 'string', 'regex:'.ThemeSetting::UNIT_REGEX],
            'theme.settings.*.maxLength' => ['sometimes', 'prohibited_unless:theme.settings.*.type,text', 'integer', 'min:1'],
        ], [
            'id.required' => 'Manifest field "id" is required and must be a string.',
            'id.string' => 'Manifest field "id" is required and must be a string.',
            'name.required' => 'Manifest field "name" is required and must be a string.',
            'name.string' => 'Manifest field "name" is required and must be a string.',
            'version.required' => 'Manifest field "version" is required and must be a string.',
            'version.string' => 'Manifest field "version" is required and must be a string.',
            'version.max' => 'Manifest field "version" must not be longer than 64 characters.',
            'provider.string' => 'Manifest "provider" must be a class name string.',
            'icon.string' => 'Manifest "icon" must be a lucide icon name or an image path string.',
            'icon.max' => 'Manifest "icon" must not be longer than 255 characters.',
            'autoload.array' => 'Manifest "autoload" must map "Vendor\\\\Prefix\\\\" to a source directory.',
            'ui.array' => 'Manifest "ui" must be an object with an "entry" path.',
            'ui.entry.required_with' => 'Manifest "ui" must be an object with an "entry" path.',
            'ui.entry.string' => 'Manifest "ui" must be an object with an "entry" path.',
            'ui.mode.string' => 'Manifest "ui.mode" must be a string.',
            'ui.prefix.filled' => 'Manifest "ui.prefix" must match '.ExtensionManifest::UI_PREFIX_REGEX.'.',
            'ui.prefix.string' => 'Manifest "ui.prefix" must match '.ExtensionManifest::UI_PREFIX_REGEX.'.',
            'ui.prefix.regex' => 'Manifest "ui.prefix" must match '.ExtensionManifest::UI_PREFIX_REGEX.'.',
            'ui.prefix.not_in' => 'Manifest "ui.prefix" is a Tailwind variant, theme namespace or panel name and cannot be used as a prefix.',
            'requires.sdk.required_with' => 'View overrides must declare a "requires.sdk" version constraint.',
            'ui.prefix.prohibited_if' => 'A theme cannot declare "ui.prefix": it builds the panel stylesheet itself.',
            'ui.views.prohibited_if' => 'A theme cannot declare "ui.views": it overrides views through defineTheme.',
            'ui.views.*.regex' => 'Manifest "ui.views" must contain view names such as "server.power" or "page.dashboard".',
            'theme.prohibited_unless' => 'Only a package with "type": "theme" can declare a "theme" section.',
            'theme.stylesheet.in' => 'Manifest "theme.stylesheet" must be "'.ThemeDefinition::STYLESHEET.'".',
            'theme.settings.*.token.regex' => 'Theme setting tokens must be custom property names such as "--accent".',
            'theme.settings.*.attribute.regex' => 'Theme setting attributes must be data attributes such as "data-density".',
            'theme.settings.*.attribute.not_in' => 'Theme setting attributes cannot use "data-theme", which the panel sets.',
            'ui.screens.*.permission.prohibited_unless' => 'Screen "permission" is only supported on server screens.',
            'ui.screens.*.nav.icon.regex' => 'Navigation "icon" must be a lucide icon name such as "life-buoy".',
            'ui.screens.*.when.eggFeatures.prohibited_unless' => 'Screen "when" egg rules are only supported on server screens.',
            'ui.screens.*.when.eggTags.prohibited_unless' => 'Screen "when" egg rules are only supported on server screens.',
            'routes.array' => 'Manifest "routes" must be an object with a "root" list.',
            'routes.root.*.regex' => 'Manifest "routes.root" prefixes must match '.ExtensionManifest::ROOT_PREFIX_REGEX.'.',
            'routes.root.*.distinct' => 'Manifest "routes.root" prefixes must be unique.',
        ]);

        if ($validator->fails()) {
            throw new InvalidExtensionException($validator->errors()->first());
        }
    }

    /** @return Closure(string, mixed, Closure(string): mixed): void */
    private function keyedBy(string $pattern, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($pattern, $message): void {
            if (is_array($value) && array_any(array_keys($value), fn (int|string $key): bool => ! is_string($key) || preg_match($pattern, $key) !== 1)) {
                $fail($message);
            }
        };
    }

    /**
     * What the structure rules cannot express: typed numbers, distinct targets and defaults
     * that pass the same checks as a saved value.
     *
     * @param  ExtensionThemeInput  $theme
     */
    private function validateTheme(string $id, array $theme): void
    {
        $extends = $theme['extends'] ?? ThemeRepository::CLASSIC;
        throw_if($extends === $id, InvalidExtensionException::class, "Theme \"{$id}\" cannot extend itself.");

        $targets = [];
        foreach ($theme['settings'] ?? [] as $key => $setting) {
            foreach (array_filter([$setting['token'] ?? null, $setting['attribute'] ?? null]) as $target) {
                if (isset($targets[$target])) {
                    throw new InvalidExtensionException("Theme settings \"{$targets[$target]}\" and \"{$key}\" both write \"{$target}\".");
                }

                $targets[$target] = $key;
            }

            $this->validateThemeSetting($key, $setting);
        }
    }

    /** @param ExtensionThemeSettingInput $setting */
    private function validateThemeSetting(string $key, array $setting): void
    {
        foreach (['min', 'max', 'step'] as $limit) {
            throw_if(array_key_exists($limit, $setting) && ! is_int($setting[$limit]) && ! is_float($setting[$limit]), InvalidExtensionException::class, "Theme setting \"{$key}\" must give \"{$limit}\" as a number.");
        }

        $this->validateThemeDefault(ThemeSetting::fromValidatedData($key, $setting));
    }

    private function validateThemeDefault(ThemeSetting $setting): void
    {
        $default = $setting->default;
        $problem = match (true) {
            $setting->min !== null && $setting->max !== null && $setting->min > $setting->max => 'has a "min" above its "max"',
            $setting->type === 'image' && $default !== null => 'cannot have a default; upload the image from the Themes page',
            $setting->type === 'text' && $default !== null && (! is_string($default) || mb_strlen($default) > $setting->maxLength) => "defaults to text of at most {$setting->maxLength} characters",
            $setting->type === 'number' && (is_int($default) || is_float($default)) && (($setting->min !== null && $default < $setting->min) || ($setting->max !== null && $default > $setting->max)) => 'has a default outside its "min" and "max"',
            default => null,
        };
        throw_if($problem !== null, InvalidExtensionException::class, "Theme setting \"{$setting->key}\" {$problem}.");

        try {
            $setting->definition();
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new InvalidExtensionException("Theme setting \"{$setting->key}\": {$invalidArgumentException->getMessage()}", $invalidArgumentException->getCode(), previous: $invalidArgumentException);
        }
    }

    /**
     * A condition has to restrict something: an empty rule, an empty matcher, or a
     * combinator with nothing to combine is a manifest mistake rather than "always".
     *
     * @param  ExtensionScreenCondition  $when
     */
    private function validateScreenCondition(array $when): void
    {
        $rules = array_intersect_key($when, ['eggFeatures' => true, 'eggTags' => true]);
        throw_if($rules === [] && ($when['runtime'] ?? false) !== true, InvalidExtensionException::class, 'Screen "when" must declare an egg rule or "runtime": true.');
        throw_if(isset($when['match']) && count($rules) < 2, InvalidExtensionException::class, 'Screen "when.match" combines "eggFeatures" with "eggTags" and requires both.');
        foreach ($rules as $name => $matcher) {
            throw_if($matcher === [], InvalidExtensionException::class, "Screen \"when.{$name}\" must list \"any\" or \"all\" values.");
        }
    }

    /**
     * A claimed top-level prefix must not shadow anything the panel already serves: the
     * SPA's client-side routes, the first segment of any registered core route, or a
     * file or directory in the public web root. Routes of extensions are named
     * `extensions.*` and are skipped - claims between extensions are compared when one
     * is enabled, not here.
     *
     * @param  list<string>  $prefixes
     */
    private function assertRootPrefixesAreFree(array $prefixes): void
    {
        if ($prefixes === []) {
            return;
        }

        $reserved = array_fill_keys(ExtensionManifest::RESERVED_ROOT_PREFIXES, true);
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $segment = explode('/', mb_ltrim($route->uri(), '/'))[0];
            if ($segment !== '' && ! str_contains($segment, '{') && ! str_starts_with($route->getName() ?? '', 'extensions.')) {
                $reserved[mb_strtolower($segment)] = true;
            }
        }

        foreach ($prefixes as $prefix) {
            throw_if(isset($reserved[$prefix]) || File::exists(public_path($prefix)), InvalidExtensionException::class, "Root path prefix \"/{$prefix}\" is reserved by the panel.");
        }
    }

    /**
     * The PSR-4 map of the package's composer.json, which Composer and editors read. It is
     * used when extension.json declares no `autoload` of its own.
     *
     * @return array<mixed>
     */
    private function composerAutoload(string $directory): array
    {
        $path = mb_rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'composer.json';
        if (! is_file($path)) {
            return [];
        }

        try {
            $composer = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidExtensionException("{$path} is not valid JSON.");
        }

        throw_unless(is_array($composer), InvalidExtensionException::class, "{$path} is not valid JSON.");

        $psr4 = data_get($composer, 'autoload.psr-4', []);
        throw_unless(is_array($psr4), InvalidExtensionException::class, 'composer.json "autoload.psr-4" must map "Vendor\\\\Prefix\\\\" to a source directory.');

        return $psr4;
    }

    /**
     * @param  array<mixed>  $autoload
     * @return array<string, string>
     */
    private function normalizeAutoload(array $autoload, string $field): array
    {
        $normalized = [];
        foreach ($autoload as $prefix => $src) {
            // Composer also maps a prefix to a list of directories; a list of one is the same map.
            if (is_array($src) && count($src) === 1 && array_is_list($src)) {
                $src = $src[0];
            }

            throw_if(! is_string($prefix) || ! is_string($src) || preg_match(ExtensionManifest::NAMESPACE_REGEX, $prefix) !== 1, InvalidExtensionException::class, $field.' must map "Vendor\\\\Prefix\\\\" to a source directory.');

            $reserved = array_find($this->panelNamespaces(), fn (string $namespace): bool => ExtensionManifest::namespacesOverlap($prefix, $namespace));
            throw_if($reserved !== null, InvalidExtensionException::class, "{$field} namespace \"{$prefix}\" overlaps \"{$reserved}\", which the panel already autoloads.");

            throw_if(str_starts_with($src, '/') || str_contains($src, '..'), InvalidExtensionException::class, $field.' directories must be relative paths inside the package.');

            $normalized[$prefix] = mb_trim(str_replace('\\', '/', $src), '/');
        }

        return $normalized;
    }

    /**
     * The namespaces of the panel, its framework and every PSR-4 prefix the panel's own
     * Composer autoloader maps. Extension class loaders run after the panel's, so they
     * never replace a class it can load, but a claim here would let an extension add
     * classes to those namespaces that the panel looks up by name.
     *
     * @return list<string>
     */
    private function panelNamespaces(): array
    {
        if ($this->panelNamespaces !== null) {
            return $this->panelNamespaces;
        }

        // Extensions register Composer loaders of their own; the panel's is the one in the
        // vendor directory this ClassLoader class was loaded from.
        $file = (new ReflectionClass(ClassLoader::class))->getFileName();
        $loader = $file === false ? null : ClassLoader::getRegisteredLoaders()[dirname($file, 2)] ?? null;

        return $this->panelNamespaces = array_values(array_unique([
            ...ExtensionManifest::RESERVED_NAMESPACES,
            ...array_keys($loader?->getPrefixesPsr4() ?? []),
        ]));
    }

    /**
     * @param  array{entry: string, mode?: string, prefix?: string, screens?: list<ExtensionScreenDefinition>, views?: list<string>}|null  $ui
     * @return array{0: string|null, 1: string}
     */
    private function normalizeUi(mixed $ui): array
    {
        if ($ui === null) {
            return [null, 'native'];
        }

        $uiEntry = str_replace('\\', '/', $ui['entry']);
        throw_if(str_starts_with($uiEntry, '/') || str_contains($uiEntry, '..'), InvalidExtensionException::class, 'Manifest "ui.entry" must be a relative path inside the package.');

        throw_if($uiEntry !== ExtensionManifest::UI_ENTRY, InvalidExtensionException::class, 'Manifest "ui.entry" must be "'.ExtensionManifest::UI_ENTRY.'".');

        $uiMode = $ui['mode'] ?? 'native';
        throw_if($uiMode !== 'native', InvalidExtensionException::class, "Unsupported ui.mode \"{$uiMode}\" - this panel supports: native.");

        return [$uiEntry, $uiMode];
    }
}
