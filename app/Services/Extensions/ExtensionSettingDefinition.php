<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Pterodactyl\Rules\ValidExtensionSettingColor;

class ExtensionSettingDefinition
{
    public const array FIELDS = ['text', 'password', 'number', 'toggle', 'select', 'color', 'textarea', 'multiselect', 'list', 'file'];

    /** Fields whose values are structured or stored elsewhere; these cannot be ->secret(). */
    private const array RICH_FIELDS = ['color', 'textarea', 'multiselect', 'list', 'file'];

    /** @var (Closure(ExtensionSettingValue): ExtensionSettingValue)|null */
    private ?Closure $normalizer = null;

    /** @var (Closure(ExtensionSettingValue): ExtensionSettingValue)|null */
    private ?Closure $publicSerializer = null;

    private ?string $label = null;

    private ?string $help = null;

    private ?string $tab = null;

    private string $field = 'text';

    /** @var list<array{value: string|int|bool, label: string}> */
    private array $options = [];

    private bool $frontend = false;

    private bool $guests = false;

    private bool $secret = false;

    /** @var 'string'|'number'|'boolean'|'array'|'object'|'null'|'json'|null */
    private ?string $frontendType = null;

    private ?int $maxLength = null;

    private ?int $maxItems = null;

    /** @var ValidationRuleSet rules every item of a list field must pass */
    private array $itemRules = [];

    private ?int $maxKilobytes = null;

    /** @var list<string> content types a file field accepts */
    private array $mimes = [];

    /**
     * @param  ExtensionSettingValue  $default
     * @param  ValidationRuleSet  $rules
     */
    public function __construct(
        public readonly string $key,
        public readonly string $input,
        public readonly mixed $default,
        public readonly array $rules = [],
        public readonly ?string $public = null,
    ) {}

    /**
     * @param  ExtensionSettingValue  $default
     * @param  ValidationRuleSet  $rules
     */
    public static function make(string $key, string $input, mixed $default, array $rules = [], ?string $public = null): self
    {
        return new self($key, $input, $default, $rules, $public);
    }

    /** @param callable(ExtensionSettingValue): ExtensionSettingValue $normalizer */
    public function normalizeUsing(callable $normalizer): self
    {
        $this->normalizer = $normalizer instanceof Closure ? $normalizer : Closure::fromCallable($normalizer);

        return $this;
    }

    /** @param callable(ExtensionSettingValue): ExtensionSettingValue $serializer */
    public function publicUsing(callable $serializer): self
    {
        $this->publicSerializer = $serializer instanceof Closure ? $serializer : Closure::fromCallable($serializer);

        return $this;
    }

    /** Human label shown on the auto-rendered admin settings form. */
    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /** Help text shown under the field on the auto-rendered admin settings form. */
    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    /**
     * Section the field is shown in on the auto-rendered admin settings form, picked from
     * a dropdown. Sections appear in the order their first field is defined; once any
     * field names one, fields without one are shown under "General".
     */
    public function tab(string $tab): self
    {
        $tab = mb_trim($tab);
        throw_if($tab === '' || mb_strlen($tab) > 64, InvalidArgumentException::class, 'An extension settings tab name must be 1 to 64 characters.');
        $this->tab = $tab;

        return $this;
    }

    /**
     * Form control for the auto-rendered admin settings form: one of
     * text, password, number, toggle, select, color, textarea, multiselect,
     * list, file. The last five have their own methods (color(), textarea(),
     * multiselect(), list(), file()) which also take their limits; naming them
     * here applies the defaults.
     *
     * @param  list<array{value: string|int|bool, label: string}>  $options  select or multiselect choices
     */
    public function field(string $field, array $options = []): self
    {
        if (! in_array($field, self::FIELDS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown settings field type "%s"; expected one of %s.', $field, implode(', ', self::FIELDS)));
        }

        return match ($field) {
            'color' => $this->color(),
            'textarea' => $this->textarea(),
            'multiselect' => $this->multiselect($options),
            'list' => $this->list(),
            'file' => $this->file(),
            default => $this->useField($field, $options),
        };
    }

    /**
     * A colour picker. The value is a hex colour (#rgb, #rgba, #rrggbb,
     * #rrggbbaa) or a numeric oklch() colour - the syntaxes theme tokens use -
     * stored in canonical lower-case form. Anything else is rejected, so the
     * value is safe to write into a style rule. Clearing it restores the default.
     */
    public function color(): self
    {
        throw_unless($this->default === null || ExtensionSettingValueGuard::color($this->default) !== null, InvalidArgumentException::class, 'A color setting defaults to a hex or oklch() colour, or null.');

        return $this->useField('color');
    }

    /** A multi-line string of at most $maxLength characters. */
    public function textarea(int $maxLength = 5000): self
    {
        throw_unless(ExtensionSettingValueGuard::text($this->default) !== null, InvalidArgumentException::class, 'A textarea setting defaults to a string.');
        throw_if($maxLength < 1, InvalidArgumentException::class, 'A textarea setting needs a positive maximum length.');
        $this->maxLength = $maxLength;

        return $this->useField('textarea');
    }

    /**
     * Any number of the declared choices. The value is the list of chosen option
     * values, deduplicated and in declaration order.
     *
     * @param  list<array{value: string|int|bool, label: string}>  $options
     */
    public function multiselect(array $options): self
    {
        throw_unless(ExtensionSettingValueGuard::strings($this->default) !== null, InvalidArgumentException::class, 'A multiselect setting defaults to a list.');
        throw_if($options === [] || array_any($options, fn (array $option): bool => $option['value'] === true || $option['value'] === false), InvalidArgumentException::class, 'A multiselect setting needs string or integer choices.');

        return $this->useField('multiselect', $options);
    }

    /**
     * An ordered list of strings. Every item must pass $itemRules (it is always
     * a string) and the list holds at most $maxItems items.
     *
     * @param  ValidationRuleSet  $itemRules
     */
    public function list(array $itemRules = ['max:255'], int $maxItems = 50): self
    {
        throw_unless(ExtensionSettingValueGuard::strings($this->default) !== null, InvalidArgumentException::class, 'A list setting defaults to a list of strings.');
        throw_if($maxItems < 1, InvalidArgumentException::class, 'A list setting needs a positive maximum item count.');
        $this->itemRules = $itemRules;
        $this->maxItems = $maxItems;

        return $this->useField('list');
    }

    /**
     * A file an administrator uploads from the settings form. The panel decides
     * the type from the file's content, stores it under a random name and serves
     * it from a fixed public URL; that URL (or the default, a URL or null, while
     * nothing is uploaded) is the setting's value. It is not part of a settings
     * update: uploading or clearing goes through its own admin endpoint.
     *
     * @param  list<string>  $mimes  accepted content types, from ExtensionSettingFiles::TYPES. SVG is only accepted when listed here.
     */
    public function file(array $mimes = ExtensionSettingFiles::IMAGES, int $maxKilobytes = 1024): self
    {
        throw_unless($this->default === null || ExtensionSettingValueGuard::text($this->default) !== null, InvalidArgumentException::class, 'A file setting defaults to a URL or null.');
        throw_if($mimes === [] || array_diff($mimes, array_keys(ExtensionSettingFiles::TYPES)) !== [], InvalidArgumentException::class, sprintf('A file setting accepts only these types: %s.', implode(', ', array_keys(ExtensionSettingFiles::TYPES))));
        throw_if($maxKilobytes < 1 || $maxKilobytes > ExtensionSettingFiles::MAX_KILOBYTES, InvalidArgumentException::class, sprintf('A file setting is limited to between 1 and %d KB.', ExtensionSettingFiles::MAX_KILOBYTES));
        $this->mimes = $mimes;
        $this->maxKilobytes = $maxKilobytes;

        return $this->useField('file');
    }

    /**
     * Expose this setting's public value to the extension's frontend bundle via
     * ctx.config. Off by default - frontend values are visible to every logged-in
     * user, so never mark secrets (even masked ones) unless that is intended.
     */
    public function frontend(bool $frontend = true): self
    {
        throw_if($frontend && $this->secret, InvalidArgumentException::class, 'Secret extension settings cannot be exposed to frontend bundles.');
        throw_if(! $frontend && $this->guests, InvalidArgumentException::class, 'Public extension settings must stay exposed to frontend bundles.');
        $this->frontend = $frontend;

        return $this;
    }

    /**
     * Also deliver this frontend setting to visitors who are not signed in (the
     * login page, for example). Only for values that are safe for anyone on the
     * internet to read; every other frontend setting stays signed-in only.
     */
    public function public(): self
    {
        throw_if($this->secret, InvalidArgumentException::class, 'Secret extension settings cannot be public.');
        throw_unless($this->frontend, InvalidArgumentException::class, 'Only frontend extension settings can be public; call ->frontend() first.');
        $this->guests = true;

        return $this;
    }

    public function secret(): self
    {
        throw_if($this->frontend, InvalidArgumentException::class, 'Secret extension settings cannot be exposed to frontend bundles.');
        throw_if(in_array($this->field, self::RICH_FIELDS, true), InvalidArgumentException::class, sprintf('A %s extension setting cannot be secret.', $this->field));
        $this->secret = true;
        $this->field = 'password';

        return $this;
    }

    public function isSecret(): bool
    {
        return $this->secret;
    }

    public function isFrontend(): bool
    {
        return $this->frontend;
    }

    public function isPublic(): bool
    {
        return $this->guests;
    }

    public function isFile(): bool
    {
        return $this->field === 'file';
    }

    /** @return array{mimes: list<string>, max_kilobytes: int} */
    public function fileConstraints(): array
    {
        return ['mimes' => $this->mimes, 'max_kilobytes' => $this->maxKilobytes ?? 0];
    }

    public function frontendType(string $type): self
    {
        throw_unless(in_array($type, ['string', 'number', 'boolean', 'array', 'object', 'null', 'json'], true), InvalidArgumentException::class, 'Invalid frontend setting type.');
        $this->frontendType = $type;

        return $this;
    }

    /**
     * The type declared with frontendType(), or the one the field implies.
     *
     * @return 'string'|'number'|'boolean'|'array'|'object'|'null'|'json'
     */
    public function declaredFrontendType(): string
    {
        return $this->frontendType ?? match ($this->field) {
            'textarea' => 'string',
            'color', 'file' => ExtensionSettingValueGuard::text($this->default) === null ? 'json' : 'string',
            'multiselect', 'list' => 'array',
            default => 'json',
        };
    }

    /**
     * Rules for a partial update, keyed by input name: the input is optional
     * and, when submitted, must pass the definition's rules and those of its
     * field type. Inputs without rules of their own are accepted as given.
     *
     * @return NormalizedValidationRules
     */
    public function validationRules(): array
    {
        $rules = in_array('sometimes', $this->rules, true) ? $this->rules : ['sometimes', ...$this->rules];

        return match ($this->field) {
            'color' => [$this->input => [...$rules, 'nullable', new ValidExtensionSettingColor]],
            'textarea' => [$this->input => [...$rules, 'nullable', 'string', 'max:'.$this->maxLength]],
            'multiselect' => [
                $this->input => [...$rules, 'array', 'list'],
                $this->input.'.*' => ['distinct', Rule::in($this->choices())],
            ],
            'list' => [
                $this->input => [...$rules, 'array', 'list', 'max:'.$this->maxItems],
                $this->input.'.*' => ['string', ...$this->itemRules],
            ],
            'file' => [$this->input => ['prohibited']],
            default => [$this->input => $rules],
        };
    }

    /**
     * Coerce a submitted or stored value to the field type's shape, then apply
     * normalizeUsing(). A value that does not fit falls back to the default.
     *
     * @param  ExtensionSettingValue  $value
     * @return ExtensionSettingValue
     */
    public function normalize(mixed $value): mixed
    {
        $value = match ($this->field) {
            'color' => ExtensionSettingValueGuard::color($value) ?? $this->default,
            'textarea' => $value === null ? '' : (ExtensionSettingValueGuard::text($value) ?? $this->default),
            'multiselect' => ExtensionSettingValueGuard::choices($value, $this->choices()) ?? $this->default,
            'list' => ExtensionSettingValueGuard::strings($value) ?? $this->default,
            default => $value,
        };

        return $this->normalizer instanceof Closure ? ($this->normalizer)($value) : $value;
    }

    /**
     * @param  ExtensionSettingValue  $value
     * @return ExtensionSettingValue
     */
    public function serializePublic(mixed $value): mixed
    {
        if ($this->secret) {
            return $value === null || $value === '' ? null : '********';
        }

        return $this->publicSerializer instanceof Closure ? ($this->publicSerializer)($value) : $value;
    }

    public function publicName(): string
    {
        return $this->public ?? $this->input;
    }

    /**
     * Schema entry for the auto-rendered admin settings form. `value` is the
     * current public serialization; secrets serialized through publicUsing()
     * therefore stay masked here too. `constraints` carries the limits the
     * field's control enforces up front, and `visibility` who receives the value.
     *
     * @param  ExtensionSettingValue  $currentValue
     * @return ExtensionSettingField
     */
    public function describe(mixed $currentValue): array
    {
        return [
            'input' => $this->input,
            'label' => $this->label ?? Str::headline($this->input),
            'help' => $this->help,
            'tab' => $this->tab,
            'field' => $this->field,
            'options' => $this->options,
            'value' => $this->serializePublic($currentValue),
            'constraints' => [
                'max_length' => $this->maxLength,
                'max_items' => $this->maxItems,
                'max_kilobytes' => $this->maxKilobytes,
                'accept' => $this->mimes,
            ],
            'visibility' => match (true) {
                $this->guests => 'public',
                $this->frontend => 'frontend',
                default => 'admin',
            },
        ];
    }

    /** @param list<array{value: string|int|bool, label: string}> $options */
    private function useField(string $field, array $options = []): self
    {
        throw_if($this->secret && in_array($field, self::RICH_FIELDS, true), InvalidArgumentException::class, sprintf('A secret extension setting cannot use the %s field.', $field));
        $this->field = $field;
        $this->options = $options;

        return $this;
    }

    /** @return list<string|int> */
    private function choices(): array
    {
        return array_values(array_filter(array_column($this->options, 'value'), fn (string|int|bool $value): bool => $value !== true && $value !== false));
    }
}
