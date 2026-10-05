<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Throwable;

/**
 * Typed key/value settings scoped to one extension, stored in extension_settings.
 * Values round-trip through JSON, so anything json-encodable is supported.
 */
class ExtensionSettings
{
    /** @var ExtensionSettingValues|null all rows, loaded once per instance on first read */
    private ?array $loaded = null;

    public function __construct(private readonly string $extension, private readonly User|Server|null $subject = null)
    {
        throw_if($subject !== null && ! $subject->exists, InvalidArgumentException::class, 'Scoped settings require a persisted user or server.');
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

    public function forUser(User $user): self
    {
        return new self($this->extension, $user);
    }

    public function forServer(Server $server): self
    {
        return new self($this->extension, $server);
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
            ->where('key', 'like', str_replace(['%', '_'], ['\\%', '\\_'], $prefix).'%')
            ->get()
            ->mapWithKeys(fn (ExtensionSetting $setting): array => [
                mb_substr($setting->key, mb_strlen($prefix)) => $setting->value === null ? null : ExtensionSettingValueGuard::decode($setting->value, $setting->is_secret),
            ])
            ->all();
    }

    public function forgetByPrefix(string $prefix): void
    {
        $this->query()
            ->where('key', 'like', str_replace(['%', '_'], ['\\%', '\\_'], $prefix).'%')
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

        $rows = [];
        foreach ($values as $key => $value) {
            $rows[] = [
                'extension' => $this->extension,
                'key' => $key,
                'scope' => $this->scope(),
                'user_id' => $this->subject instanceof User ? $this->subject->id : null,
                'server_id' => $this->subject instanceof Server ? $this->subject->id : null,
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
        return match (true) {
            $this->subject instanceof User => 'user:'.$this->subject->id,
            $this->subject instanceof Server => 'server:'.$this->subject->id,
            default => 'global',
        };
    }

    /** @return Builder<ExtensionSetting> */
    private function query(): Builder
    {
        return ExtensionSetting::query()->where('extension', $this->extension)->where('scope', $this->scope());
    }
}
