<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

/**
 * Typed key/value settings scoped to one extension, stored in extension_settings.
 * Values round-trip through JSON, so anything json-encodable is supported.
 */
class ExtensionSettings
{
    /**
     * The models settings can be scoped to, keyed by morph alias. Each has a cascading
     * `<alias>_id` column on extension_settings, so scoped settings go with their model.
     */
    public const array SCOPES = [
        'user' => User::class,
        'server' => Server::class,
        'node' => Node::class,
        'egg' => Egg::class,
        'location' => Location::class,
        'mount' => Mount::class,
        'database_host' => DatabaseHost::class,
    ];

    /** @var ExtensionSettingValues|null all rows, loaded once per instance on first read */
    private ?array $loaded = null;

    public function __construct(
        private readonly string $extension,
        private readonly ?Model $subject = null,
        private readonly bool $fields = false,
    ) {
        if ($subject instanceof Model) {
            throw_unless(in_array($subject::class, self::SCOPES, true), InvalidArgumentException::class, sprintf('Extension settings cannot be scoped to %s.', $subject::class));
            throw_unless($subject->exists, InvalidArgumentException::class, 'Scoped settings require a persisted model.');
        }

        throw_if($fields && ! $subject instanceof Model, InvalidArgumentException::class, 'Field values belong to a model.');
    }

    /** @param list<self> $settings */
    public static function preload(array $settings): void
    {
        $pending = [];
        foreach ($settings as $setting) {
            if ($setting->loaded === null) {
                $pending[$setting->extension.'|'.$setting->scope()] = $setting;
            }
        }

        if ($pending === []) {
            return;
        }

        $rows = ExtensionSetting::query()
            ->whereIn('extension', array_map(fn (self $setting): string => $setting->extension, $pending))
            ->whereIn('scope', array_map(fn (self $setting): string => $setting->scope(), $pending))
            ->get()->groupBy(fn (ExtensionSetting $row): string => $row->extension.'|'.$row->scope);
        foreach ($pending as $identifier => $setting) {
            try {
                $values = [];
                foreach ($rows->get($identifier) ?? [] as $row) {
                    $values[$row->key] = $row->value === null ? null : ExtensionSettingValueGuard::decode($row->value, $row->is_secret);
                }

                $setting->loaded = $values;
            } catch (Throwable) {
                // Leave invalid settings to the existing per-extension error boundary.
            }
        }
    }

    /** The id of the extension these settings belong to. */
    public function extension(): string
    {
        return $this->extension;
    }

    /** Settings scoped to one user, server, node, egg, location, mount or database host. */
    public function for(Model $subject): self
    {
        return new self($this->extension, $subject);
    }

    /**
     * The values of the extension's admin form fields for one model, which the panel stores
     * when its Fields class has no values() and save(). They are kept apart from `for()`, so
     * settings an extension shows or lets its users change never include them.
     */
    public function fields(Model $subject): self
    {
        return new self($this->extension, $subject, fields: true);
    }

    public function forUser(User $user): self
    {
        return $this->for($user);
    }

    public function forServer(Server $server): self
    {
        return $this->for($server);
    }

    /**
     * @param  ExtensionSettingValue  $default
     * @return ExtensionSettingValue
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $loaded = $this->loaded ??= $this->all();

        return array_key_exists($key, $loaded) ? $loaded[$key] : $default;
    }

    /** @return ExtensionSettingValues */
    public function all(): array
    {
        $values = [];
        foreach ($this->query()->get() as $row) {
            $values[$row->key] = $row->value === null ? null : ExtensionSettingValueGuard::decode($row->value, $row->is_secret);
        }

        return $this->loaded = $values;
    }

    /** @param ExtensionSettingValue $value */
    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /** @param ExtensionSettingValue $value */
    public function setSecret(string $key, mixed $value): void
    {
        $this->setManySecrets([$key => $value], [$key]);
    }

    /** @param ExtensionSettingValues $values */
    public function setMany(array $values): void
    {
        $this->persist($values, []);
    }

    /**
     * @param  ExtensionSettingValues  $values
     * @param  list<string>  $secretKeys
     */
    public function setManySecrets(array $values, array $secretKeys): void
    {
        $this->persist($values, $secretKeys);
    }

    public function forget(string $key): void
    {
        $this->query()
            ->where('key', $key)
            ->delete();

        unset($this->loaded[$key]);
    }

    /** @return ExtensionSettingValues keys matching the prefix, prefix stripped */
    public function getByPrefix(string $prefix): array
    {
        return $this->query()
            ->where('key', 'like', $this->startsWith($prefix))
            ->get()
            ->mapWithKeys(fn (ExtensionSetting $setting): array => [
                mb_substr($setting->key, mb_strlen($prefix)) => $setting->value === null ? null : ExtensionSettingValueGuard::decode($setting->value, $setting->is_secret),
            ])
            ->all();
    }

    public function forgetByPrefix(string $prefix): void
    {
        $this->query()
            ->where('key', 'like', $this->startsWith($prefix))
            ->delete();

        $this->loaded = null;
    }

    /**
     * @param  ExtensionSettingValues  $values
     * @param  list<string>  $secretKeys
     */
    private function persist(array $values, array $secretKeys): void
    {
        if ($values === []) {
            return;
        }

        $subjects = [];
        foreach (self::SCOPES as $alias => $model) {
            $subjects[$alias.'_id'] = $this->subject instanceof $model ? $this->subjectKey() : null;
        }

        $rows = [];
        foreach ($values as $key => $value) {
            $rows[] = [
                'extension' => $this->extension,
                'key' => $key,
                'scope' => $this->scope(),
                ...$subjects,
                'is_secret' => in_array($key, $secretKeys, true),
                'value' => ExtensionSettingValueGuard::encode($value, in_array($key, $secretKeys, true)),
            ];
        }

        ExtensionSetting::query()->upsert($rows, ['extension', 'scope', 'key'], ['value', 'is_secret', 'updated_at']);

        if ($this->loaded !== null) {
            $this->loaded = array_replace($this->loaded, $values);
        }
    }

    private function scope(): string
    {
        if (! $this->subject instanceof Model) {
            return 'global';
        }

        return ($this->fields ? 'fields:' : '').$this->subject->getMorphClass().':'.$this->subjectKey();
    }

    /** A LIKE pattern for keys starting with the prefix, its wildcards and escape character taken literally. */
    private function startsWith(string $prefix): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix).'%';
    }

    private function subjectKey(): int
    {
        return JsonValueGuard::integer($this->subject?->getKey());
    }

    /** @return Builder<ExtensionSetting> */
    private function query(): Builder
    {
        return ExtensionSetting::query()->where('extension', $this->extension)->where('scope', $this->scope());
    }
}
