<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionHeadTagTest;

use InvalidArgumentException;
use Pterodactyl\Services\Extensions\ExtensionHeadTag;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

test('renders allow-listed attributes in a fixed order and escapes their values', function (array $entry, string $html): void {
    expect(ExtensionHeadTag::fromArray($entry)->toHtml())->toBe($html);
})->with([
    'named meta' => [['content' => '#0f172a', 'name' => 'theme-color', 'tag' => 'meta'], '<meta name="theme-color" content="#0f172a">'],
    'property meta' => [['tag' => 'meta', 'property' => 'og:title', 'content' => 'Panel & "friends" <b>'], '<meta property="og:title" content="Panel &amp; &quot;friends&quot; &lt;b&gt;">'],
    'media scoped meta' => [['tag' => 'meta', 'name' => 'theme-color', 'content' => '#fff', 'media' => '(prefers-color-scheme: light)'], '<meta name="theme-color" content="#fff" media="(prefers-color-scheme: light)">'],
    'empty content' => [['tag' => 'meta', 'name' => 'apple-mobile-web-app-capable', 'content' => ''], '<meta name="apple-mobile-web-app-capable" content="">'],
    'manifest link' => [['tag' => 'link', 'rel' => 'manifest', 'href' => '/extensions/pwa/manifest.webmanifest', 'crossorigin' => 'use-credentials'], '<link rel="manifest" href="/extensions/pwa/manifest.webmanifest" crossorigin="use-credentials">'],
    'icon link' => [['tag' => 'link', 'rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => 'https://cdn.example.com/icon.png?v=2&s=1', 'type' => 'image/png'], '<link rel="apple-touch-icon" href="https://cdn.example.com/icon.png?v=2&amp;s=1" sizes="180x180" type="image/png">'],
    'mask icon' => [['tag' => 'link', 'rel' => 'mask-icon', 'href' => '/favicons/mask.svg', 'color' => '#bc6e3c'], '<link rel="mask-icon" href="/favicons/mask.svg" color="#bc6e3c">'],
]);

test('rejects everything outside the meta and link allow-lists', function (array $entry): void {
    expect(fn (): ExtensionHeadTag => ExtensionHeadTag::fromArray($entry))->toThrow(InvalidArgumentException::class);
})->with([
    'script' => [['tag' => 'script', 'src' => '/x.js']],
    'style' => [['tag' => 'style', 'content' => 'body{}']],
    'base' => [['tag' => 'base', 'href' => 'https://evil.example']],
    'missing tag' => [['name' => 'theme-color', 'content' => '#000']],
    'event handler' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/icon.png', 'onload' => 'alert(1)']],
    'inline style' => [['tag' => 'meta', 'name' => 'theme-color', 'content' => '#000', 'style' => 'x']],
    'http-equiv' => [['tag' => 'meta', 'http-equiv' => 'refresh', 'content' => '0;url=https://evil.example']],
    'charset' => [['tag' => 'meta', 'charset' => 'utf-7']],
    'name and property' => [['tag' => 'meta', 'name' => 'a', 'property' => 'b', 'content' => 'c']],
    'neither name nor property' => [['tag' => 'meta', 'content' => 'c']],
    'missing content' => [['tag' => 'meta', 'name' => 'theme-color']],
    'non-string content' => [['tag' => 'meta', 'name' => 'theme-color', 'content' => ['#000']]],
    'attribute injection in a name' => [['tag' => 'meta', 'name' => 'x" onload="alert(1)', 'content' => 'c']],
    'panel-owned csrf token' => [['tag' => 'meta', 'name' => 'CSRF-Token', 'content' => 'forged']],
    'panel-owned viewport' => [['tag' => 'meta', 'name' => 'viewport', 'content' => 'width=1']],
    'panel-owned robots' => [['tag' => 'meta', 'name' => 'robots', 'content' => 'index']],
    'control characters' => [['tag' => 'meta', 'name' => 'description', 'content' => "line\nbreak"]],
    'oversized value' => [['tag' => 'meta', 'name' => 'description', 'content' => str_repeat('a', 2049)]],
    'stylesheet' => [['tag' => 'link', 'rel' => 'stylesheet', 'href' => '/x.css']],
    'preload' => [['tag' => 'link', 'rel' => 'preload', 'href' => '/x.js']],
    'modulepreload' => [['tag' => 'link', 'rel' => 'modulepreload', 'href' => '/x.js']],
    'compound relation' => [['tag' => 'link', 'rel' => 'icon stylesheet', 'href' => '/x.css']],
    'missing href' => [['tag' => 'link', 'rel' => 'icon']],
    'javascript url' => [['tag' => 'link', 'rel' => 'icon', 'href' => 'javascript:alert(1)']],
    'data url' => [['tag' => 'link', 'rel' => 'icon', 'href' => 'data:image/png;base64,AAAA']],
    'protocol-relative url' => [['tag' => 'link', 'rel' => 'icon', 'href' => '//evil.example/icon.png']],
    'backslash url' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/\\evil.example/icon.png']],
    'relative path' => [['tag' => 'link', 'rel' => 'icon', 'href' => 'icon.png']],
    'other scheme' => [['tag' => 'link', 'rel' => 'icon', 'href' => 'ftp://example.com/icon.png']],
    'url without host' => [['tag' => 'link', 'rel' => 'icon', 'href' => 'https:///icon.png']],
    'url with whitespace' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/icon.png onload=x']],
    'bad sizes' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/icon.png', 'sizes' => '180']],
    'bad type' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/icon.png', 'type' => 'text/javascript"']],
    'bad crossorigin' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/icon.png', 'crossorigin' => 'yes']],
    'meta attribute on a link' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/icon.png', 'content' => 'x']],
    'integer keyed attribute' => [['tag' => 'link', 'rel' => 'icon', 'href' => '/icon.png', 0 => 'onload=x']],
]);

test('identifies elements a document should only carry once', function (): void {
    $light = ExtensionHeadTag::fromArray(['tag' => 'meta', 'name' => 'Theme-Color', 'content' => '#fff']);
    $dark = ExtensionHeadTag::fromArray(['tag' => 'meta', 'name' => 'theme-color', 'content' => '#000']);
    $small = ExtensionHeadTag::fromArray(['tag' => 'link', 'rel' => 'icon', 'href' => '/16.png', 'sizes' => '16x16']);
    $large = ExtensionHeadTag::fromArray(['tag' => 'link', 'rel' => 'icon', 'href' => '/32.png', 'sizes' => '32x32']);
    $manifest = ExtensionHeadTag::fromArray(['tag' => 'link', 'rel' => 'manifest', 'href' => '/a.webmanifest']);
    $other = ExtensionHeadTag::fromArray(['tag' => 'link', 'rel' => 'manifest', 'href' => '/b.webmanifest']);

    expect($light->identity())->toBe($dark->identity())->toBe('meta:theme-color');
    expect($small->identity())->not->toBe($large->identity());
    expect($small->replaces())->toBe($large->replaces())->toBe('link:icon');
    expect($manifest->identity())->toBe($other->identity())->toBe('link:manifest');
});
