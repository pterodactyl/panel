<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions\Scaffolding;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Themes\ThemeDefinition;
use Pterodactyl\Services\Themes\ThemeRepository;

/**
 * Scaffolds a new extension package the way `php artisan make:*` scaffolds core
 * classes: plain stub substitution, no templating engine. The result is a valid,
 * installable package - `npm run build` and `p:extension:install` away from
 * running in the panel.
 */
class ExtensionScaffolder
{
    /** @var string[] package-relative paths written by the last scaffold() call */
    private array $files = [];

    public function __construct(private readonly ExtensionManifestValidator $validator) {}

    /**
     * Scaffolds <outDir>/<id>/ and returns the created package path. The manifest
     * is re-validated through the normal loader before returning, so a scaffold
     * that succeeds is guaranteed installable.
     */
    public function scaffold(
        string $id,
        ?string $name = null,
        ?string $description = null,
        ?string $author = null,
        bool $ui = true,
        ?string $outDir = null,
        bool $force = false,
        ?string $prefix = null,
        bool $theme = false,
    ): string {
        $this->files = [];

        throw_unless(preg_match(ExtensionManifest::ID_REGEX, $id), InvalidExtensionException::class, "Extension id \"{$id}\" must match ".ExtensionManifest::ID_REGEX.'.');

        throw_if(in_array($id, ExtensionManifest::RESERVED_IDS, true), InvalidExtensionException::class, "Extension id \"{$id}\" is reserved.");

        $prefix ??= ExtensionManifest::defaultUiPrefix($id);
        throw_unless($theme || ExtensionManifest::isUsableUiPrefix($prefix), InvalidExtensionException::class, "Tailwind prefix \"{$prefix}\" must match ".ExtensionManifest::UI_PREFIX_REGEX.' and must not be a Tailwind variant, theme namespace or panel name.');

        $target = mb_rtrim($outDir ?? config()->string('extensions.directory'), '/\\').DIRECTORY_SEPARATOR.$id;
        throw_if(is_dir($target) && ! $force, InvalidExtensionException::class, "{$target} already exists - pass --force to overwrite it.");

        File::deleteDirectory($target);
        File::ensureDirectoryExists($target);

        $namespace = Str::studly(str_replace('-', '_', $id));
        $replacements = [
            '{{id}}' => $id,
            '{{name}}' => $name ?: Str::headline($id),
            '{{description}}' => $description ?? '',
            '{{author}}' => $author ?? '',
            '{{namespace}}' => $namespace,
            '{{provider}}' => $namespace.'Provider',
            '{{sdkDependency}}' => $this->sdkDependency($target),
            '{{prefix}}' => $prefix,
        ];

        if ($theme) {
            $this->scaffoldTheme($target, $id, $replacements);
            $this->validator->fromDirectory($target);

            return $target;
        }

        $this->writeManifest($target, $id, $namespace, $replacements, $ui);
        $this->writeComposerJson($target, $namespace, $replacements);

        $this->stub($target, 'provider.stub', 'src/'.$namespace.'Provider.php', $replacements);
        $this->stub($target, 'controller.stub', 'src/Http/Controllers/StatusController.php', $replacements);
        $this->stub($target, 'routes-client.stub', 'routes/client.php', $replacements);
        $this->stub($target, 'gitignore.stub', '.gitignore', $replacements);

        File::ensureDirectoryExists($target.'/database/migrations');

        if ($ui) {
            $this->stub($target, 'client-index.stub', 'src/client/index.tsx', $replacements);
            $this->stub($target, 'client-server-screen.stub', 'src/client/screens/ServerScreen.tsx', $replacements);
            $this->stub($target, 'vite.config.stub', 'vite.config.mjs', $replacements);
            $this->stub($target, 'tsconfig.stub', 'tsconfig.json', $replacements);
            $this->stub($target, 'package.stub', 'package.json', $replacements);
            $this->stub($target, 'client-styles.stub', 'src/client/styles.css', $replacements);
            $this->stub($target, 'postcss.config.stub', 'postcss.config.mjs', $replacements);
            $this->stub($target, 'vitest.config.stub', 'vitest.config.mjs', $replacements);
            $this->stub($target, 'client-test.stub', 'src/client/screens/ServerScreen.spec.tsx', $replacements);
            $this->stub($target, 'openapi.config.stub', 'openapi-ts.config.ts', $replacements);
            $this->stub($target, 'externalize-api.stub', 'scripts/externalize-api.mjs', $replacements);
            $this->stub($target, 'ci.stub', '.github/workflows/ci.yml', $replacements);
        }

        $replacements['{{frontend}}'] = $ui
            ? strtr($this->stubContents('readme-frontend.stub'), $replacements)
            : '';
        $this->stub($target, 'readme.stub', 'README.md', $replacements);

        // A scaffold the panel itself cannot load is a bug - fail loudly here.
        $this->validator->fromDirectory($target);

        return $target;
    }

    /** @return string[] package-relative paths created by the last scaffold() call */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * @param  array<string, string>  $replacements  stub placeholder => literal substitution
     */
    private function writeManifest(string $target, string $id, string $namespace, array $replacements, bool $ui): void
    {
        $manifest = array_filter([
            '$schema' => $ui ? './node_modules/@pterodactyl/sdk/manifest.schema.json' : null,
            'id' => $id,
            'name' => $replacements['{{name}}'],
            'version' => '1.0.0',
            'requires' => ['panel' => '^2.0.0-dev', 'sdk' => $this->sdkConstraint(), 'php' => '^8.3'],
            'description' => $replacements['{{description}}'] ?: null,
            'author' => $replacements['{{author}}'] ?: null,
            'provider' => $namespace.'\\'.$namespace.'Provider',
            'ui' => $ui ? ['entry' => ExtensionManifest::UI_ENTRY, 'mode' => 'native', 'prefix' => $replacements['{{prefix}}'], 'screens' => [['id' => 'main', 'area' => 'server', 'path' => $id, 'nav' => ['label' => $replacements['{{name}}']]]]] : null,
        ], fn (array|string|null $value): bool => $value !== null);

        File::put(
            $target.DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );
        $this->files[] = ExtensionManifest::FILENAME;
    }

    /**
     * A theme with a stylesheet for both schemes, a skin, a layout override, four settings and a
     * test, laid out as the SDK's THEMES.md describes. It needs no PHP; a provider can be
     * added like an extension's.
     *
     * @param  array<string, string>  $replacements  stub placeholder => literal substitution
     */
    private function scaffoldTheme(string $target, string $id, array $replacements): void
    {
        $manifest = array_filter([
            '$schema' => './node_modules/@pterodactyl/sdk/manifest.schema.json',
            'id' => $id,
            'type' => 'theme',
            'name' => $replacements['{{name}}'],
            'version' => '1.0.0',
            'requires' => ['panel' => '^2.0.0-dev', 'sdk' => $this->sdkConstraint()],
            'description' => $replacements['{{description}}'] ?: null,
            'author' => $replacements['{{author}}'] ?: null,
            'icon' => 'palette',
            'ui' => ['entry' => ExtensionManifest::UI_ENTRY],
            'theme' => [
                'extends' => ThemeRepository::CLASSIC,
                'stylesheet' => ThemeDefinition::STYLESHEET,
                'schemes' => ThemeDefinition::SCHEMES,
                'settings' => [
                    'accent' => ['type' => 'color', 'label' => 'Accent', 'default' => '#8b5cf6', 'token' => "--{$id}-accent"],
                    'radius' => ['type' => 'number', 'label' => 'Corner radius', 'default' => 0.75, 'min' => 0, 'max' => 1.5, 'step' => 0.125, 'unit' => 'rem', 'token' => '--radius'],
                    'compact' => ['type' => 'toggle', 'label' => 'Compact spacing', 'default' => false, 'attribute' => "data-{$id}-compact"],
                    'logo' => ['type' => 'image', 'label' => 'Logo', 'help' => 'Shown in place of the panel name.', 'token' => '--brand-logo'],
                ],
            ],
        ], fn (array|string|null $value): bool => $value !== null);

        File::put($target.DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)."\n");
        $this->files[] = ExtensionManifest::FILENAME;

        $replacements['{{artisan}}'] = $this->artisanPath($target);
        foreach ([
            'theme-package.stub' => 'package.json',
            'theme-vite.config.stub' => 'vite.config.mjs',
            'postcss.config.stub' => 'postcss.config.mjs',
            'theme-tsconfig.stub' => 'tsconfig.json',
            'vitest.config.stub' => 'vitest.config.mjs',
            'theme-css.stub' => 'src/theme.css',
            'theme-tsx.stub' => 'src/theme.tsx',
            'theme-skins.stub' => 'src/skins.ts',
            'theme-layout.stub' => 'src/views/AppLayout.tsx',
            'theme-test.stub' => 'src/theme.spec.tsx',
            'gitignore.stub' => '.gitignore',
            'theme-readme.stub' => 'README.md',
        ] as $stub => $relative) {
            $this->stub($target, $stub, $relative, $replacements);
        }
    }

    /** The `requires.sdk` constraint of a new package: this SDK or a later 2.x one. */
    private function sdkConstraint(): string
    {
        return '>='.config()->string('extensions.sdk_version').' <3.0';
    }

    /** The panel's artisan as the package's npm scripts reach it: relative when they share a root. */
    private function artisanPath(string $target): string
    {
        $relative = $this->relativePath($target, base_path());

        return $relative === null ? str_replace('\\', '/', base_path('artisan')) : mb_rtrim($relative, '/').'/artisan';
    }

    /**
     * The PSR-4 map lives in composer.json, where Composer, editors and static analysis read
     * it, and the panel reads it from there too. The autoloader suffix keeps the class a
     * bundled vendor/autoload.php declares apart from every other package's.
     *
     * @param  array<string, string>  $replacements  stub placeholder => literal substitution
     */
    private function writeComposerJson(string $target, string $namespace, array $replacements): void
    {
        $composer = array_filter([
            'description' => $replacements['{{description}}'] ?: null,
            'autoload' => ['psr-4' => [$namespace.'\\' => 'src/']],
            'config' => ['autoloader-suffix' => $namespace.'Extension'],
        ], fn (array|string|null $value): bool => $value !== null);

        File::put($target.DIRECTORY_SEPARATOR.'composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $this->files[] = 'composer.json';
    }

    /**
     * @param  array<string, string>  $replacements  stub placeholder => literal substitution
     */
    private function stub(string $target, string $stub, string $relative, array $replacements): void
    {
        $path = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, strtr($this->stubContents($stub), $replacements));
        $this->files[] = $relative;
    }

    private function stubContents(string $stub): string
    {
        $contents = file_get_contents(__DIR__.'/stubs/'.$stub);
        throw_if($contents === false, InvalidExtensionException::class, "Unable to read extension scaffold stub [{$stub}].");

        return $contents;
    }

    /**
     * The @pterodactyl/sdk devDependency for the generated package.json: a
     * relative file: link to the panel's own SDK package when it is present
     * (npm publish is still pending), otherwise the SDK's version range.
     */
    private function sdkDependency(string $target): string
    {
        $sdk = base_path('packages'.DIRECTORY_SEPARATOR.'sdk');
        if (! is_file($sdk.DIRECTORY_SEPARATOR.'package.json')) {
            return '^'.config()->string('extensions.sdk_version');
        }

        $relative = $this->relativePath($target, $sdk);
        if ($relative !== null) {
            return 'file:'.$relative;
        }

        $version = File::json($sdk.DIRECTORY_SEPARATOR.'package.json')['version'] ?? null;

        return '^'.(is_string($version) ? $version : config()->string('extensions.sdk_version'));
    }

    private function relativePath(string $from, string $to): ?string
    {
        $from = realpath($from);
        $to = realpath($to);
        if ($from === false || $to === false) {
            return null;
        }

        $fromParts = explode(DIRECTORY_SEPARATOR, $from);
        $toParts = explode(DIRECTORY_SEPARATOR, $to);
        $shared = 0;
        while (isset($fromParts[$shared], $toParts[$shared]) && $fromParts[$shared] === $toParts[$shared]) {
            $shared++;
        }

        // Different roots (e.g. Windows drives) - no relative path exists.
        if ($shared === 0) {
            return null;
        }

        return str_repeat('../', count($fromParts) - $shared).implode('/', array_slice($toParts, $shared));
    }
}
