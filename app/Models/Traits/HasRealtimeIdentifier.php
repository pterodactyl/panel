<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ParagonIE\ConstantTime\Base32;
use Pterodactyl\Models\Attributes\Identifiable;
use Pterodactyl\Support\JsonValueGuard;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

/**
 * Support realtime identifiers on models that do not track an "identifier" column in
 * the database. This allows us to make use of the existing data reliant on UUID columns
 * while still allowing for output and querying against a more human readable identifier
 * value.
 *
 * @property-read string $identifier
 *
 * @method static Builder<static> whereIdentifier(string $identifier)
 *
 * @mixin Model
 */
trait HasRealtimeIdentifier
{
    private static string $identifierPrefix;

    private static string $identifierDataColumn;

    /**
     * @param  Builder<Model>  $builder  matches the Pterodactyl\Contracts\Models\Identifiable
     *                                   contract; Eloquent always hands the scope a builder for
     *                                   the model the trait is used on
     */
    #[Scope]
    public function whereIdentifier(Builder $builder, string $identifier): void
    {
        if (! str_starts_with($identifier, $prefix = self::$identifierPrefix.'_')) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $bytes = rescue(fn (): string => Base32::decode(Str::replaceFirst($prefix, '', $identifier)), report: false);
        if (empty($bytes)) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $builder->where(self::$identifierDataColumn, Uuid::fromBytes($bytes)->toString());
    }

    protected static function bootHasRealtimeIdentifier(): void
    {
        $attrs = (new ReflectionClass(static::class))->getAttributes(Identifiable::class);

        throw_if(count($attrs) !== 1, InvalidArgumentException::class, 'The #['.Identifiable::class.'] attribute must be set on '.static::class.' to use realtime identifiers.');

        $instance = $attrs[0]->newInstance();

        self::$identifierPrefix = $instance->prefix;
        self::$identifierDataColumn = $instance->column;
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function identifier(): Attribute
    {
        return Attribute::get(function (): string {
            $bytes = Uuid::fromString(JsonValueGuard::string($this->getRawOriginal(self::$identifierDataColumn)))->getBytes();

            return sprintf('%s_%s', self::$identifierPrefix, Base32::encodeUnpadded($bytes));
        });
    }
}
