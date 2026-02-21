<?php

namespace Pterodactyl\Models\Traits;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;
use ParagonIE\ConstantTime\Base32;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\Attributes\Identifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Support realtime identifiers on models that do not track an "identifier" column in
 * the database. This allows us to make use of the existing data reliant on UUID columns
 * while still allowing for output and querying against a more human readable identifier
 * value.
 *
 * @property-read string $identifier
 *
 * @method static Builder whereIdentifier(string $identifier)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasRealtimeIdentifier
{
    private static string $identifierPrefix;

    private static string $identifierDataColumn;

    protected function identifier(): Attribute
    {
        return Attribute::get(function () {
            $bytes = Uuid::fromString($this->getRawOriginal(static::$identifierDataColumn))->getBytes();

            return sprintf('%s_%s', static::$identifierPrefix, Base32::encodeUnpadded($bytes));
        });
    }

    public function scopeWhereIdentifier(Builder $builder, string $identifier): void
    {
        if (!str_starts_with($identifier, $prefix = self::$identifierPrefix . '_')) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $bytes = rescue(fn () => Base32::decode(Str::replaceFirst($prefix, '', $identifier)), report: false);
        if (empty($bytes)) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $builder->where(self::$identifierDataColumn, Uuid::fromBytes($bytes)->toString());
    }

    protected static function bootHasRealtimeIdentifier(): void
    {
        $attrs = (new \ReflectionClass(static::class))->getAttributes(Identifiable::class);

        Assert::count(
            $attrs,
            1,
            'The #[' . Identifiable::class . '] attribute must be set on ' . static::class . ' to use realtime identifiers.'
        );

        $instance = $attrs[0]->newInstance();

        self::$identifierPrefix = $instance->prefix;
        self::$identifierDataColumn = $instance->column;
    }
}
