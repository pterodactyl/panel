<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read EggVariable $variable
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class ServerVariable extends Model
{
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
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<EggVariable, $this>
     */
    public function variable(): BelongsTo
    {
        return $this->belongsTo(EggVariable::class, 'variable_id');
    }
}
