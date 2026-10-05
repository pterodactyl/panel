<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * Install-state record for an extension package on disk.
 *
 * @property int $id
 * @property string $identifier
 * @property string $version
 * @property bool $enabled
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['identifier', 'version', 'enabled', 'error'])]
class Extension extends Model
{
    public const string RESOURCE_NAME = 'extension';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'bool',
        ];
    }
}
