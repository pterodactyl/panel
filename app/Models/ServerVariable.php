<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property int $variable_id
 * @property string $variable_value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property EggVariable $variable
 * @property Server $server
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class ServerVariable extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'server_variable';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'server_id' => 'integer',
            'variable_id' => 'integer',
        ];
    }

    /**
     * Returns the server this variable is associated with.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Returns information about a given variables parent.
     *
     * @return BelongsTo<EggVariable, $this>
     */
    public function variable(): BelongsTo
    {
        return $this->belongsTo(EggVariable::class, 'variable_id');
    }
}
