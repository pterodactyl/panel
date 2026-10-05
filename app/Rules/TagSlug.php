<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Pterodactyl\Models\Tag;

/**
 * A tag slug may not be purely numeric, because every Panel id is an integer:
 * reserving that shape both makes a stale legacy nest id (still sent by outdated
 * billing modules) unambiguously droppable, and keeps the id-or-slug lookup
 * ({@see Tag::whereKeyOrSlug()}) free of slugs shadowing tag ids.
 */
class TagSlug implements ValidationRule
{
    /** Purely numeric - a legacy nest id, or a tag's own primary key. */
    public const string NUMERIC = '/^\d+$/';

    /**
     * Whether a value is the reserved shape, so callers - inline tag creation, the
     * deploy path - can drop it rather than treat it as a real slug.
     */
    public static function isReserved(string $value): bool
    {
        return preg_match(self::NUMERIC, $value) === 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (self::isReserved($value)) {
            $fail('A tag slug cannot be purely numeric (reserved for tag and legacy nest IDs).');
        }
    }
}
