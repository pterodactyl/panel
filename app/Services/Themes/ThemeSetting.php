<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Closure;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;

/**
 * One entry of a theme's `theme.settings`. Values are validated and stored as an extension
 * setting of the theme, and rendered into a `:root` token and/or an `<html>` attribute.
 */
final readonly class ThemeSetting
{
    public const array TYPES = ['color', 'select', 'toggle', 'number', 'text', 'image'];

    public const string KEY_REGEX = '/^[a-zA-Z][a-zA-Z0-9_]{0,47}$/D';

    public const string TOKEN_REGEX = '/^--[a-zA-Z][a-zA-Z0-9-]{0,62}$/D';

    public const string ATTRIBUTE_REGEX = '/^data-[a-z][a-z0-9-]{0,58}$/D';

    /** Option keys end up in tokens and attributes, so they are CSS identifiers. */
    public const string OPTION_REGEX = '/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/D';

    public const string UNIT_REGEX = '/^(?:%|[a-z]{1,4})$/D';

    /** Attributes the panel itself writes on `<html>`. */
    public const array RESERVED_ATTRIBUTES = ['data-theme'];

    /** Stored apart from the settings a theme's provider registers. */
    public const string KEY_PREFIX = 'theme.';

    public const array IMAGE_TYPES = [...ExtensionSettingFiles::IMAGES, 'image/svg+xml'];

    /**
     * @param  array<string, string>  $options  select option key => label
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public string|int|float|bool|null $default = null,
        public ?string $help = null,
        public ?string $token = null,
        public ?string $attribute = null,
        public array $options = [],
        public int|float|null $min = null,
        public int|float|null $max = null,
        public int|float|null $step = null,
        public ?string $unit = null,
        public int $maxLength = 255,
    ) {}

    /** @param ExtensionThemeSettingInput $data */
    public static function fromValidatedData(string $key, array $data): self
    {
        return new self(
            key: $key,
            type: $data['type'],
            label: $data['label'],
            default: $data['default'] ?? null,
            help: $data['help'] ?? null,
            token: $data['token'] ?? null,
            attribute: $data['attribute'] ?? null,
            options: $data['options'] ?? [],
            min: $data['min'] ?? null,
            max: $data['max'] ?? null,
            step: $data['step'] ?? null,
            unit: $data['unit'] ?? null,
            maxLength: $data['maxLength'] ?? 255,
        );
    }

    /** A double-quoted CSS string in which every character but a safe few is a hex escape. */
    public static function cssString(string $value): string
    {
        $escaped = preg_replace_callback(
            '/[^A-Za-z0-9 _.,:\/?=&%#@!+-]/u',
            fn (array $match): string => '\\'.dechex(mb_ord($match[0])).' ',
            $value,
        );

        return '"'.$escaped.'"';
    }

    /**
     * The extension setting that validates and stores this setting's value, with the same
     * rules as the matching `->color()`, `->field()` and `->file()` definitions. A cleared
     * value reads as the default.
     */
    public function definition(): ExtensionSettingDefinition
    {
        $definition = ExtensionSettingDefinition::make(self::KEY_PREFIX.$this->key, $this->key, $this->default, $this->rules())
            ->label($this->label)
            ->normalizeUsing(fn (mixed $value): mixed => $value ?? $this->default);
        if ($this->help !== null) {
            $definition->help($this->help);
        }

        return match ($this->type) {
            'color' => $definition->color(),
            'select' => $definition->field('select', array_map(fn (string $value, string $label): array => ['value' => $value, 'label' => $label], array_keys($this->options), $this->options)),
            'image' => $definition->file(self::IMAGE_TYPES, ExtensionSettingFiles::MAX_KILOBYTES),
            default => $definition->field($this->type),
        };
    }

    /** A stored value while it still fits the setting as declared now, else the default. */
    public function value(mixed $value): string|int|float|bool|null
    {
        $value = match (true) {
            $this->type === 'color' => ExtensionSettingValueGuard::color($value),
            $this->type === 'select' => is_string($value) && isset($this->options[$value]) ? $value : null,
            $this->type === 'toggle' => is_bool($value) ? $value : null,
            $this->type === 'number' => (is_int($value) || is_float($value)) && ($this->min === null || $value >= $this->min) && ($this->max === null || $value <= $this->max) ? $value : null,
            default => is_string($value) ? $value : null,
        };

        return $value ?? $this->default;
    }

    /**
     * The value as a CSS declaration value. Only values of the setting's own type are
     * written, and anything that is not a colour, number or identifier is a quoted,
     * escaped string, so it can never leave its declaration or the style element.
     */
    public function tokenValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $this->type === 'color' => ExtensionSettingValueGuard::color($value),
            $this->type === 'toggle' => is_bool($value) ? ($value ? '1' : '0') : null,
            $this->type === 'number' => is_int($value) || is_float($value) ? $this->number($value).($this->unit ?? '') : null,
            $this->type === 'select' => is_string($value) && isset($this->options[$value]) ? $value : null,
            $this->type === 'image' => is_string($value) ? 'url('.self::cssString($value).')' : null,
            default => is_string($value) ? self::cssString($value) : null,
        };
    }

    /** The value of the `<html>` attribute; the document escapes it. */
    public function attributeValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) || is_float($value) => $this->number($value),
            is_string($value) => $value,
            default => null,
        };
    }

    private function number(int|float $value): string
    {
        return is_int($value) ? (string) $value : mb_rtrim(mb_rtrim(sprintf('%.6F', $value), '0'), '.');
    }

    /** @return ValidationRuleSet */
    private function rules(): array
    {
        return match ($this->type) {
            'number' => array_values(array_filter([
                'nullable',
                'numeric',
                $this->min !== null ? 'min:'.$this->min : null,
                $this->max !== null ? 'max:'.$this->max : null,
                $this->step !== null ? $this->stepRule() : null,
            ])),
            'text' => ['nullable', 'string', 'max:'.$this->maxLength],
            'select', 'toggle' => ['nullable'],
            default => [],
        };
    }

    /** @return Closure(string, mixed, Closure(string): mixed): void */
    private function stepRule(): Closure
    {
        $step = (float) $this->step;
        $base = (float) ($this->min ?? 0);

        return function (string $attribute, mixed $value, Closure $fail) use ($step, $base): void {
            $steps = is_numeric($value) ? ((float) $value - $base) / $step : 0.5;
            if (abs($steps - round($steps)) > 1e-9) {
                $fail("The :attribute must be in steps of {$this->step}.");
            }
        };
    }
}
