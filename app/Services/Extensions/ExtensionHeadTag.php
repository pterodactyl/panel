<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Pterodactyl\Support\JsonValueGuard;

/**
 * One validated `<meta>` or `<link>` element an extension adds to the panel's document
 * head. Only these two void elements exist here: there is no way to express a script, a
 * stylesheet, inline style or an event handler, and every attribute comes from a fixed
 * allow-list and is escaped when rendered.
 */
final readonly class ExtensionHeadTag
{
    /** The most tags a single extension may register. */
    public const int LIMIT = 32;

    /** @var array<'meta'|'link', list<string>> */
    public const array ATTRIBUTES = [
        'meta' => ['name', 'property', 'content', 'media'],
        'link' => ['rel', 'href', 'sizes', 'type', 'media', 'color', 'crossorigin', 'hreflang', 'title'],
    ];

    /** Link relations that cannot load or execute anything in the page. */
    public const array LINK_RELATIONS = [
        'manifest',
        'icon',
        'shortcut icon',
        'apple-touch-icon',
        'apple-touch-icon-precomposed',
        'apple-touch-startup-image',
        'mask-icon',
        'alternate',
        'canonical',
        'search',
        'preconnect',
        'dns-prefetch',
    ];

    /** Meta names the panel owns; `http-equiv` and `charset` are not expressible at all. */
    public const array RESERVED_META_NAMES = ['csrf-token', 'viewport', 'robots', 'referrer'];

    /** Elements of which a document should only carry one: an extension's replaces the panel's. */
    private const array SINGLETON_LINK_RELATIONS = ['manifest', 'canonical', 'mask-icon', 'apple-touch-icon', 'shortcut icon'];

    private const string TOKEN_REGEX = '/^[A-Za-z0-9][A-Za-z0-9:._-]{0,63}$/';

    /** Printable text only: no control characters, so a value can never break out of its line. */
    private const string TEXT_REGEX = '/^[^\x00-\x1F\x7F]*$/u';

    /** An absolute http(s) URL or a path on this panel (never protocol-relative). */
    private const string URL_REGEX = '~^(?:https?://[^\s/?#\\\\]+(?:[/?#][^\s\\\\]*)?|/(?![/\\\\])[^\s\\\\]*)$~i';

    /**
     * @param  'meta'|'link'  $tag
     * @param  array<string, string>  $attributes
     */
    private function __construct(
        public string $tag,
        public array $attributes,
    ) {}

    /**
     * @param  array<array-key, ApiValue9>  $entry  `['tag' => 'meta'|'link', <attribute> => <value>, ...]`
     *
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $entry): self
    {
        $tag = $entry['tag'] ?? null;
        throw_unless($tag === 'meta' || $tag === 'link', InvalidArgumentException::class, 'Head tags must set "tag" to "meta" or "link".');

        $text = ['string', 'max:2048', 'regex:'.self::TEXT_REGEX];
        $validator = Validator::make($entry, $tag === 'meta' ? [
            'tag' => ['required'],
            'name' => ['required_without:property', 'prohibits:property', 'string', 'regex:'.self::TOKEN_REGEX],
            'property' => ['required_without:name', 'string', 'regex:'.self::TOKEN_REGEX],
            'content' => ['present', ...$text],
            'media' => ['sometimes', 'required', ...$text],
        ] : [
            'tag' => ['required'],
            'rel' => ['required', 'string', Rule::in(self::LINK_RELATIONS)],
            'href' => ['required', ...$text, 'regex:'.self::URL_REGEX],
            'sizes' => ['sometimes', 'required', 'string', 'regex:/^(?:any|[1-9][0-9]{0,4}x[1-9][0-9]{0,4})(?: [1-9][0-9]{0,4}x[1-9][0-9]{0,4})*$/i'],
            'type' => ['sometimes', 'required', 'string', 'regex:~^[a-z0-9][a-z0-9.+-]{0,63}/[a-z0-9][a-z0-9.+-]{0,63}$~i'],
            'media' => ['sometimes', 'required', ...$text],
            'color' => ['sometimes', 'required', 'string', 'max:64', 'regex:/^[#A-Za-z0-9(),.% -]+$/'],
            'crossorigin' => ['sometimes', 'required', 'string', 'in:anonymous,use-credentials'],
            'hreflang' => ['sometimes', 'required', 'string', 'regex:/^[A-Za-z0-9-]{1,35}$/'],
            'title' => ['sometimes', 'required', ...$text],
        ]);

        $unknown = array_diff(array_keys($entry), ['tag', ...self::ATTRIBUTES[$tag]]);
        throw_if($unknown !== [], InvalidArgumentException::class, sprintf('Head tag <%s> does not support the "%s" attribute.', $tag, reset($unknown)));
        throw_if($validator->fails(), InvalidArgumentException::class, sprintf('Invalid <%s> head tag: %s', $tag, $validator->errors()->first()));

        $attributes = [];
        foreach (self::ATTRIBUTES[$tag] as $attribute) {
            $value = $validator->validated()[$attribute] ?? null;
            if ($value !== null) {
                $attributes[$attribute] = JsonValueGuard::string($value);
            }
        }

        throw_if(in_array(mb_strtolower($attributes['name'] ?? ''), self::RESERVED_META_NAMES, true), InvalidArgumentException::class, sprintf('The "%s" meta tag is managed by the panel.', $attributes['name'] ?? ''));

        return new self($tag, $attributes);
    }

    /**
     * What this element is, for de-duplication: a meta name/property, a link relation that
     * a document should carry once, or otherwise the exact element.
     */
    public function identity(): string
    {
        if ($this->tag === 'meta') {
            return 'meta:'.mb_strtolower($this->attributes['name'] ?? $this->attributes['property'] ?? '');
        }

        $relation = mb_strtolower($this->attributes['rel'] ?? '');

        return in_array($relation, self::SINGLETON_LINK_RELATIONS, true)
            ? 'link:'.$relation
            : 'link:'.$relation.':'.implode('|', $this->attributes);
    }

    /** The key core's own head elements are looked up by, e.g. `meta:theme-color` or `link:icon`. */
    public function replaces(): string
    {
        return $this->tag === 'meta'
            ? $this->identity()
            : 'link:'.mb_strtolower($this->attributes['rel'] ?? '');
    }

    public function toHtml(): string
    {
        $html = '<'.$this->tag;
        foreach ($this->attributes as $name => $value) {
            $html .= ' '.$name.'="'.e($value).'"';
        }

        return $html.'>';
    }
}
