<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\EggFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use RuntimeException;

/**
 * @property array<string, string> $docker_images
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

    public const string RESOURCE_NAME = 'egg';

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
            'config_startup' => 'string',
            'config_logs' => 'string',
            'config_files' => 'string',
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
        return $this->tags->map(fn (Tag $tag): string => $tag->slug)->all();
    }

    /**
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'egg_id');
    }

    /**
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
     * @return BelongsTo<self, $this>
     */
    public function scriptFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copy_script_from');
    }

    /**
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
