<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionStylesheetInspectorTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionStylesheetInspector;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

// What Tailwind 4.3 and Lightning CSS emit for an extension built with `prefix(hw)`.
const PREFIXED = <<<'CSS'
    /*! tailwindcss v4.3.0 | MIT License | https://tailwindcss.com */
    @layer properties{@supports (((-webkit-hyphens:none)) and (not (margin-trim:inline))){*,:before,:after,::backdrop{--tw-space-y-reverse:0;--tw-content:""}}}
    @layer theme{:root,:host{--hw-text-sm:.875rem;--hw-text-sm--line-height:calc(1.25 / .875);--hw-color-brand:#f0f}}
    @layer utilities{.hw\:\@container{container-type:inline-size}.hw\:flex{display:flex}.hw\:mt-4\!{margin-top:calc(var(--spacing) * 4)!important}.hw\:-mt-2{margin-top:calc(var(--spacing) * -2)}.hw\:w-\[13px\]{width:13px}.hw\:bg-background{background-color:var(--background)}:where(.hw\:space-y-2>:not(:last-child)){--tw-space-y-reverse:0}:is(.hw\:\*\:p-2>*){padding:calc(var(--spacing) * 2)}.hw\:before\:content-\[\"\{x\}\"\]:before{--tw-content:"{x}";content:var(--tw-content)}.hw\:group-hover\:underline:is(:where(.hw\:group):hover *),.hw\:dark\:bg-card:is(.dark *){text-decoration-line:underline}@media (hover:hover){.hw\:hover\:bg-accent:hover{background-color:var(--accent)}}@media (width>=64rem){.hw\:lg\:hidden{display:none}}}
    @property --tw-space-y-reverse{syntax:"*";inherits:false;initial-value:0}@keyframes spin{to{transform:rotate(360deg)}}
    .my-banner{display:flex;color:var(--foreground)}.my-banner:hover{--local:1}
    CSS;

// The same build before minification: nested rules stay inside the utility they belong to.
const PREFIXED_NESTED = <<<'CSS'
    @layer theme {
      :root, :host {
        --hw-text-sm: 0.875rem;
      }
    }
    @layer utilities {
      .hw\:space-y-2 {
        :where(& > :not(:last-child)) {
          --tw-space-y-reverse: 0;
        }
      }
      .hw\:lg\:hidden {
        @media (width >= 64rem) {
          display: none;
        }
      }
      .hw\:hover\:bg-accent {
        &:hover {
          @media (hover: hover) {
            background-color: var(--accent);
          }
        }
      }
    }
    CSS;

// The scaffolded stylesheet with `prefix(hw)` removed: what every extension shipped before the prefix.
const UNPREFIXED = <<<'CSS'
    @layer theme{:root,:host{--spacing:var(--spacing);--text-sm:.875rem;--font-sans:var(--font-sans)}}
    @layer utilities{.flex{display:flex}.hidden{display:none}.p-4{padding:calc(var(--spacing) * 4)}@media (width>=64rem){.lg\:hidden{display:none}}}
    CSS;

/** Write a built package around one stylesheet and ask the inspector about it. */
function conflictReason(string $css, ?string $prefix, string $stylesheet = 'dist/assets/index.css', bool $ui = true): ?string
{
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-stylesheets-'.uniqid();
    File::ensureDirectoryExists(dirname($directory.'/'.$stylesheet));
    File::put($directory.'/extension.json', json_encode(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', ...($ui ? ['ui' => array_filter(['entry' => 'dist/client.js', 'prefix' => $prefix])] : [])], JSON_THROW_ON_ERROR));
    File::put($directory.'/dist/client.js', 'export default {};');
    File::put($directory.'/'.$stylesheet, $css);

    try {
        return (new ExtensionStylesheetInspector)->conflictReason((new ExtensionManifestValidator)->fromDirectory($directory));
    } finally {
        File::deleteDirectory($directory);
    }
}

test('a build with the declared prefix has nothing to report', function (string $css): void {
    expect((new ExtensionStylesheetInspector)->foreign($css, 'hw'))->toBe(['utilities' => [], 'theme' => []]);
})->with([
    'minified' => [PREFIXED],
    'nested' => [PREFIXED_NESTED],
    'no tailwind at all' => ['.my-banner{display:flex}@media (width>=64rem){.my-banner{display:none}}'],
    'empty' => [''],
]);

test('reports unprefixed utilities and theme variables', function (): void {
    expect((new ExtensionStylesheetInspector)->foreign(UNPREFIXED, 'hw'))->toBe([
        'utilities' => ['.flex', '.hidden', '.p-4', '.lg\:hidden'],
        'theme' => ['--spacing', '--text-sm', '--font-sans'],
    ]);
});

test('reports utilities and theme variables built with the prefix of another extension', function (): void {
    $inspector = new ExtensionStylesheetInspector;
    $css = '@layer theme{:root,:host{--hw-text-sm:.875rem}}@layer utilities{.hw\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}@media (width>=40rem){.hw\:sm\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}}}';

    expect($inspector->foreign($css, 'hw'))->toBe(['utilities' => [], 'theme' => []]);
    expect($inspector->foreign($css, 'ext'))->toBe(['utilities' => ['.hw\:grid-cols-2', '.hw\:sm\:grid-cols-3'], 'theme' => ['--hw-text-sm']]);
    // `h` is not `hw`: the prefix ends at the escaped colon and the hyphen.
    expect($inspector->foreign($css, 'h'))->toBe(['utilities' => ['.hw\:grid-cols-2', '.hw\:sm\:grid-cols-3'], 'theme' => ['--hw-text-sm']]);
});

test('two stylesheets accepted under different prefixes cannot define the same selector', function (): void {
    // The bug: both extensions built with one shared prefix, so the later `.ext\:grid-cols-2`
    // overrode the earlier `.ext\:sm\:grid-cols-3` and a responsive grid collapsed.
    $build = fn (string $prefix): string => "@layer theme{:root,:host{--{$prefix}-text-sm:.875rem}}@layer utilities{.{$prefix}\\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}@media (width>=40rem){.{$prefix}\\:sm\\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}}}";
    $inspector = new ExtensionStylesheetInspector;
    $names = function (string $css) use ($inspector): array {
        $all = $inspector->foreign($css, null);

        return [...$all['utilities'], ...$all['theme']];
    };

    expect($names($build('ext')))->toBe(['.ext\:grid-cols-2', '.ext\:sm\:grid-cols-3', '--ext-text-sm']);
    expect(array_intersect($names($build('ext')), $names($build('ext'))))->not->toBe([]);

    expect(conflictReason($build('aa'), 'aa'))->toBeNull();
    expect(conflictReason($build('bb'), 'bb'))->toBeNull();
    expect(array_intersect($names($build('aa')), $names($build('bb'))))->toBe([]);
    // Whatever one extension is accepted with, the other is rejected for.
    expect(conflictReason($build('aa'), 'bb'))->toContain('without its "bb" prefix', '.aa\:grid-cols-2, .aa\:sm\:grid-cols-3, --aa-text-sm');
});

test('only looks inside the tailwind layers', function (): void {
    $css = '.flex{display:flex}:root{--spacing:1rem}@layer components{.flex{display:flex}:root{--card:red}}@layer utilities{@keyframes pulse{50%{opacity:.5}}}';

    expect((new ExtensionStylesheetInspector)->foreign($css, 'hw'))->toBe(['utilities' => [], 'theme' => []]);
    expect((new ExtensionStylesheetInspector)->foreign($css, null))->toBe(['utilities' => [], 'theme' => []]);
});

test('reads past comments, strings and escapes that contain block syntax', function (): void {
    $css = '/* } @layer utilities{.a{} */@layer utilities{.hw\:a[data-x="}{,"],.b[data-x=\'}\']{color:red}.hw\:c\,d{color:red}/* .e{} */.f{color:red}}.g{color:red}';

    expect((new ExtensionStylesheetInspector)->foreign($css, 'hw'))->toBe(['utilities' => ['.b[data-x=\'}\']', '.f'], 'theme' => []]);
});

test('names the stylesheet, what it found and the prefix to build with', function (string $css, ?string $expected): void {
    $reason = conflictReason($css, 'hw');

    if ($expected === null) {
        expect($reason)->toBeNull();
    } else {
        expect($reason)->toContain('Extension "probe" ships '.$expected.' in dist/assets/index.css', 'without its "hw" prefix', 'build Tailwind with `prefix(hw)`', '`hw:flex`');
    }
})->with([
    'prefixed' => [PREFIXED, null],
    'utilities and theme' => [UNPREFIXED, 'Tailwind utilities and theme variables'],
    'utilities' => ['@layer utilities{.flex{display:flex}}', 'Tailwind utilities'],
    'theme' => ['@layer theme{:root{--spacing:.25rem}}', 'Tailwind theme variables'],
    'another prefix' => ['@layer utilities{.ext\:flex{display:flex}}', 'Tailwind utilities'],
]);

test('a build with Tailwind layers needs a declared prefix and is told which line to add', function (): void {
    expect(conflictReason(PREFIXED, null))->toContain(
        'Extension "probe" ships Tailwind utilities and theme variables in dist/assets/index.css',
        'but declares no Tailwind prefix - add "prefix": "probe" to "ui" in extension.json, build Tailwind with `prefix(probe)`, write its classes as `probe:flex`',
    );
    expect(conflictReason('@layer utilities{.flex{display:flex}}', null))->toContain('ships Tailwind utilities in', 'add "prefix": "probe"');
});

test('a build without Tailwind layers needs no prefix', function (): void {
    expect(conflictReason('.my-banner{display:flex}@layer components{.my-card{display:grid}}@layer utilities{}', null))->toBeNull();
});

test('lists a handful of the offending names', function (): void {
    expect(conflictReason('@layer utilities{.a{}.b{}.c{}.d{}.e{}.f{}.g{}}', 'hw', 'dist/index.css'))->toContain('(.a, .b, .c, .d, .e, ...)');
});

test('an extension without a frontend has no stylesheets to inspect', function (): void {
    expect(conflictReason('@layer utilities{.flex{display:flex}}', null, 'dist/index.css', ui: false))->toBeNull();
});
