<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\EggFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use Pterodactyl\Support\JsonValueGuard;
use RuntimeException;

/**
 * @property int $id
 * @property string $uuid
 * @property string $author
 * @property string $name
 * @property string|null $description
 * @property list<string>|null $features
 * @property string $docker_image -- deprecated, use $docker_images
 * @property array<string, string> $docker_images
 * @property string $update_url
 * @property bool $force_outgoing_ip
 * @property list<string>|null $file_denylist
 * @property string|null $config_files
 * @property string|null $config_startup
 * @property string|null $config_logs
 * @property string|null $config_stop
 * @property int|null $config_from
 * @property string|null $startup
 * @property bool $script_is_privileged
 * @property string|null $script_install
 * @property string $script_entry
 * @property string $script_container
 * @property int|null $copy_script_from
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $copy_script_install
 * @property string $copy_script_entry
 * @property string $copy_script_container
 * @property string|null $inherit_config_files
 * @property string|null $inherit_config_startup
 * @property string|null $inherit_config_logs
 * @property string|null $inherit_config_stop
 * @property list<string>|null $inherit_file_denylist
 * @property list<string>|null $inherit_features
 * @property Collection|Server[] $servers
 * @property Collection|EggVariable[] $variables
 * @property Collection|Egg[] $configuredChildren
 * @property Egg|null $scriptFrom
 * @property Egg|null $configFrom
 */
#[Attributes\Identifiable('eegg')]
#[Fillable([
    'name',
    'description',
    'features',
    'docker_images',
    'force_outgoing_ip',
    'file_denylist',
    'config_files',
    'config_startup',
    'config_logs',
    'config_stop',
    'config_from',
    'startup',
    'script_is_privileged',
    'script_install',
    'script_entry',
    'script_container',
    'copy_script_from',
])]
class Egg extends Model implements Identifiable
{
    /** @use HasFactory<EggFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;
    use HasVersion4Uuids;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'egg';

    /**
     * Defines the current egg export version.
     */
    public const string EXPORT_VERSION = 'PTDL_v2';

    /**
     * Features that toggle frontend functionality for an egg, inherited from the parent
     * egg it copies configuration from unless explicitly set to an empty array ("[]").
     */
    public const string FEATURE_EULA_POPUP = 'eula';

    public const string FEATURE_FASTDL = 'fastdl';

    protected $attributes = [
        'features' => null,
        'file_denylist' => null,
        'config_stop' => null,
        'config_startup' => null,
        'config_logs' => null,
        'config_files' => null,
        'update_url' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config_from' => 'integer',
            'script_is_privileged' => 'boolean',
            'force_outgoing_ip' => 'boolean',
            'copy_script_from' => 'integer',
            'features' => 'array',
            'docker_images' => 'array',
            'file_denylist' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * The tags applied to this egg. These replace the grouping the egg's nest used
     * to provide, and are what the deployment gate matches a node's accepted games
     * against.
     *
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id');
    }

    /**
     * The slugs of this egg's tags, used to gate node selection.
     *
     * @return string[]
     */
    public function tagSlugs(): array
    {
        return JsonValueGuard::stringList($this->tags->pluck('slug')->all());
    }

    /**
     * Gets all servers associated with this egg.
     *
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'egg_id');
    }

    /**
     * Gets all variables associated with this egg.
     *
     * @return HasMany<EggVariable, $this>
     */
    public function variables(): HasMany
    {
        return $this->hasMany(EggVariable::class, 'egg_id');
    }

    /**
     * Eggs that inherit their configuration from this egg.
     *
     * @return HasMany<self, $this>
     */
    public function configuredChildren(): HasMany
    {
        return $this->hasMany(self::class, 'config_from');
    }

    /**
     * Get the parent egg from which to copy scripts.
     *
     * @return BelongsTo<self, $this>
     */
    public function scriptFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copy_script_from');
    }

    /**
     * Get the parent egg from which to copy configuration settings.
     *
     * @return BelongsTo<self, $this>
     */
    public function configFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'config_from');
    }

    /**
     * Returns the install script for the egg; if egg is copying from another
     * it will return the copied script.
     *
     * @return Attribute<string|null, never>
     */
    protected function copyScriptInstall(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (($this->script_install) !== null || ($this->copy_script_from) === null) {
                return $this->script_install;
            }

            return $this->copiedScriptEgg()->script_install;
        });
    }

    /**
     * Returns the entry command for the egg; if egg is copying from another
     * it will return the copied entry command.
     *
     * @return Attribute<string, never>
     */
    protected function copyScriptEntry(): Attribute
    {
        return Attribute::make(get: function (): string {
            if (($this->copy_script_from) === null) {
                return $this->script_entry;
            }

            return $this->copiedScriptEgg()->script_entry;
        });
    }

    /**
     * Returns the install container for the egg; if egg is copying from another
     * it will return the copied install container.
     *
     * @return Attribute<string, never>
     */
    protected function copyScriptContainer(): Attribute
    {
        return Attribute::make(get: function (): string {
            if (($this->copy_script_from) === null) {
                return $this->script_container;
            }

            return $this->copiedScriptEgg()->script_container;
        });
    }

    /**
     * Return the file configuration for an egg.
     *
     * @return Attribute<string|null, never>
     */
    protected function inheritConfigFiles(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (($this->config_files) !== null || ($this->config_from) === null) {
                return $this->config_files;
            }

            return $this->inheritedConfigurationEgg()->config_files;
        });
    }

    /**
     * Return the startup configuration for an egg.
     *
     * @return Attribute<string|null, never>
     */
    protected function inheritConfigStartup(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (($this->config_startup) !== null || ($this->config_from) === null) {
                return $this->config_startup;
            }

            return $this->inheritedConfigurationEgg()->config_startup;
        });
    }

    /**
     * Return the log reading configuration for an egg.
     *
     * @return Attribute<string|null, never>
     */
    protected function inheritConfigLogs(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (($this->config_logs) !== null || ($this->config_from) === null) {
                return $this->config_logs;
            }

            return $this->inheritedConfigurationEgg()->config_logs;
        });
    }

    /**
     * Return the stop command configuration for an egg.
     *
     * @return Attribute<string|null, never>
     */
    protected function inheritConfigStop(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (($this->config_stop) !== null || ($this->config_from) === null) {
                return $this->config_stop;
            }

            return $this->inheritedConfigurationEgg()->config_stop;
        });
    }

    /**
     * Returns the features available to this egg from the parent configuration if there are
     * no features defined for this egg specifically and there is a parent egg configured.
     *
     * @return Attribute<list<string>|null, never>
     */
    protected function inheritFeatures(): Attribute
    {
        return Attribute::make(get: function (): ?array {
            if (($this->features) !== null || ($this->config_from) === null) {
                return $this->features;
            }

            return $this->inheritedConfigurationEgg()->features;
        });
    }

    /**
     * Returns the features available to this egg from the parent configuration if there are
     * no features defined for this egg specifically and there is a parent egg configured.
     *
     * @return Attribute<list<string>|null, never>
     */
    protected function inheritFileDenylist(): Attribute
    {
        return Attribute::make(get: function (): ?array {
            if (($this->config_from) === null) {
                return $this->file_denylist;
            }

            return $this->inheritedConfigurationEgg()->file_denylist;
        });
    }

    /**
     * Eggs whose install script is their own.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function scriptSources(Builder $query): void
    {
        $query->whereNull('copy_script_from');
    }

    private function copiedScriptEgg(): self
    {
        return $this->scriptFrom ?? throw new RuntimeException('The configured script source egg does not exist.');
    }

    private function inheritedConfigurationEgg(): self
    {
        return $this->configFrom ?? throw new RuntimeException('The configured parent egg does not exist.');
    }
}
