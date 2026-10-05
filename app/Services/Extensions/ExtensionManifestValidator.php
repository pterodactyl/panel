<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use JsonException;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use UnexpectedValueException;

class ExtensionManifestValidator
{
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

        $autoload = $this->normalizeAutoload($data['autoload'] ?? []);
        [$uiEntry, $uiMode] = $this->normalizeUi($data['ui'] ?? null);

        $provider = $data['provider'] ?? null;
        throw_if($provider !== null && $provider === '', InvalidExtensionException::class, 'Manifest "provider" must be a class name string.');

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
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string'],
            'provider' => ['nullable', 'string'],
            'autoload' => ['sometimes', 'array'],
            'requires' => ['sometimes', 'array:panel,sdk,php,extensions'],
            'requires.panel' => ['sometimes', 'string', 'max:255'],
            'requires.sdk' => ['required_with:ui.components', 'string', 'max:255'],
            'requires.php' => ['sometimes', 'string', 'max:255'],
            'requires.extensions' => ['sometimes', 'array', 'max:64'],
            'requires.extensions.*' => ['required', 'string', 'max:255'],
            'ui' => ['sometimes', 'array'],
            'ui.entry' => ['required_with:ui', 'string'],
            'ui.mode' => ['sometimes', 'string'],
            'ui.prefix' => ['sometimes', 'filled', 'string', 'regex:'.ExtensionManifest::UI_PREFIX_REGEX, Rule::notIn(ExtensionManifest::RESERVED_UI_PREFIXES)],
            'ui.components' => ['sometimes', 'array', 'list', 'max:64'],
            'ui.components.*' => ['required', 'string', 'distinct:strict', Rule::in(ExtensionManifest::COMPONENT_NAMES)],
            'ui.screens' => ['sometimes', 'array', 'list', 'max:64'],
            'ui.screens.*' => ['required', 'array:id,area,path,nav,permission,parent,when'],
            'ui.screens.*.id' => ['required', 'string', 'max:48', 'regex:/^[a-z][a-z0-9-]*$/', 'distinct:strict'],
            'ui.screens.*.area' => ['required', 'string', 'in:account,server,admin'],
            'ui.screens.*.path' => ['required', 'string', 'max:255', 'regex:~^[a-z][a-z0-9-]*(?:/(?:[a-z][a-z0-9-]*|\\$[a-zA-Z][a-zA-Z0-9_]*))*/?$~'],
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
        ], [
            'id.required' => 'Manifest field "id" is required and must be a string.',
            'id.string' => 'Manifest field "id" is required and must be a string.',
            'name.required' => 'Manifest field "name" is required and must be a string.',
            'name.string' => 'Manifest field "name" is required and must be a string.',
            'version.required' => 'Manifest field "version" is required and must be a string.',
            'version.string' => 'Manifest field "version" is required and must be a string.',
            'version.max' => 'Manifest field "version" must not be longer than 64 characters.',
            'provider.string' => 'Manifest "provider" must be a class name string.',
            'autoload.array' => 'Manifest "autoload" must map "Vendor\\\\Prefix\\\\" to a source directory.',
            'ui.array' => 'Manifest "ui" must be an object with an "entry" path.',
            'ui.entry.required_with' => 'Manifest "ui" must be an object with an "entry" path.',
            'ui.entry.string' => 'Manifest "ui" must be an object with an "entry" path.',
            'ui.mode.string' => 'Manifest "ui.mode" must be a string.',
            'ui.prefix.filled' => 'Manifest "ui.prefix" must match '.ExtensionManifest::UI_PREFIX_REGEX.'.',
            'ui.prefix.string' => 'Manifest "ui.prefix" must match '.ExtensionManifest::UI_PREFIX_REGEX.'.',
            'ui.prefix.regex' => 'Manifest "ui.prefix" must match '.ExtensionManifest::UI_PREFIX_REGEX.'.',
            'ui.prefix.not_in' => 'Manifest "ui.prefix" is a Tailwind variant, theme namespace or panel name and cannot be used as a prefix.',
            'requires.sdk.required_with' => 'Component replacements must declare a "requires.sdk" version constraint.',
            'ui.components.*.in' => 'Manifest "ui.components" must contain supported component names.',
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
     * @param  array<array-key, ApiValue9>  $autoload
     * @return array<string, string>
     */
    private function normalizeAutoload(mixed $autoload): array
    {
        $normalized = [];
        foreach ($autoload as $prefix => $src) {
            throw_if(! is_string($prefix) || ! is_string($src) || ! str_ends_with($prefix, '\\'), InvalidExtensionException::class, 'Manifest "autoload" must map "Vendor\\\\Prefix\\\\" to a source directory.');

            throw_if(str_starts_with($src, '/') || str_contains($src, '..'), InvalidExtensionException::class, 'Manifest "autoload" directories must be relative paths inside the package.');

            $normalized[$prefix] = mb_trim(str_replace('\\', '/', $src), '/');
        }

        return $normalized;
    }

    /**
     * @param  array{entry: string, mode?: string, prefix?: string, screens?: list<ExtensionScreenDefinition>, components?: list<string>}|null  $ui
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
