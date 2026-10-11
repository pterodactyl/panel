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
 * @property string|null $error_phase where the last failure happened: a load phase (register, boot, compatibility) or a runtime one
 * @property Carbon|null $errored_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['identifier', 'version', 'enabled', 'error', 'error_phase', 'errored_at'])]
class Extension extends Model
{
    public const string RESOURCE_NAME = 'extension';

    /**
     * Failures that come from loading the extension. Each boot checks these again, so a
     * clean boot clears them; any other failure stays until the extension is next
     * enabled, disabled or installed.
     *
     * @var list<string>
     */
    public const array LOAD_PHASES = ['register', 'boot', 'compatibility'];

    /** No failure recorded. */
    public const array NO_ERROR = ['error' => null, 'error_phase' => null, 'errored_at' => null];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'bool',
            'errored_at' => 'datetime',
        ];
    }
}
