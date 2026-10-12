<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

/**
 * What one page renders with: the theme chain, its settings as tokens and `<html>`
 * attributes, the colour scheme and the stylesheet to link.
 */
final readonly class ThemeDocument
{
    /**
     * @param  list<array{id: string, version: string, entry: string|null, development: array{url: string, version: string, scripts: string}|null}>  $chain
     * @param  array<string, string|int|float|bool|null>  $settings
     * @param  array<string, string>  $tokens  custom property => escaped CSS value
     * @param  array<string, string>  $attributes  data attribute => unescaped value
     * @param  'dark'|'light'  $scheme
     * @param  non-empty-list<'dark'|'light'>  $schemes
     */
    public function __construct(
        public string $id = ThemeRepository::CLASSIC,
        public array $chain = [],
        public array $settings = [],
        public array $tokens = [],
        public array $attributes = [],
        public string $scheme = 'dark',
        public array $schemes = ThemeRepository::CLASSIC_SCHEMES,
        public bool $systemScheme = false,
        public bool $safeMode = false,
        public bool $preview = false,
        public ?string $stylesheet = null,
        public bool $stylesheetFallback = false,
    ) {}

    /**
     * `window.SiteConfiguration.theme`, sent to guests and signed-in users alike.
     *
     * @return array<string, mixed>
     */
    public function bootstrap(): array
    {
        return [
            'id' => $this->id,
            'chain' => $this->chain,
            'settings' => (object) $this->settings,
            'scheme' => $this->scheme,
            'schemes' => $this->schemes,
            'safeMode' => $this->safeMode,
            'preview' => $this->preview,
            'stylesheetFallback' => $this->stylesheetFallback,
        ];
    }

    /** @return array<string, string> the attributes of `<html>` besides `lang` */
    public function htmlAttributes(): array
    {
        return ['class' => $this->scheme, 'data-theme' => $this->id, ...$this->attributes];
    }

    /** The rule for `<style id="theme-settings">`. Every value was escaped by its setting. */
    public function style(): string
    {
        if ($this->tokens === []) {
            return '';
        }

        $declarations = '';
        foreach ($this->tokens as $property => $value) {
            $declarations .= "{$property}:{$value};";
        }

        // Doubled so the settings outrank the stylesheet's own :root and .dark declarations.
        return ':root:root{'.$declarations.'}';
    }

    /** The panel's stylesheet is linked unless the theme's own, built against this panel, replaces it. */
    public function linksCoreStylesheet(): bool
    {
        return $this->stylesheet === null || $this->stylesheetFallback;
    }

    /** @return list<string> */
    public function modulePreloads(): array
    {
        return array_values(array_filter(array_column($this->chain, 'entry')));
    }
}
