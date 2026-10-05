<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Backups;

use Aws\S3\S3Client;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Support\JsonValueGuard;

class BackupManager
{
    protected ConfigRepository $config;

    /**
     * The array of resolved backup drivers.
     *
     * @var array<string, FilesystemAdapter>
     */
    protected array $adapters = [];

    /** @var array<string, Closure(Application, BackupDiskConfig): FilesystemAdapter> Registered custom driver creators. */
    protected array $customCreators = [];

    /**
     * BackupManager constructor.
     */
    public function __construct(protected Application $app)
    {
        $this->config = $app->make(ConfigRepository::class);
    }

    /**
     * Returns a backup adapter instance.
     */
    public function adapter(?string $name = null): FilesystemAdapter
    {
        return $this->get($name ?: $this->getDefaultAdapter());
    }

    /**
     * Set the given backup adapter instance.
     */
    public function set(string $name, FilesystemAdapter $disk): self
    {
        $this->adapters[$name] = $disk;

        return $this;
    }

    /**
     * Creates a new Wings adapter.
     *
     * @param  BackupDiskConfig  $config
     */
    public function createWingsAdapter(array $config): FilesystemAdapter
    {
        return new InMemoryFilesystemAdapter;
    }

    /**
     * Creates a new S3 adapter.
     *
     * @param  S3BackupDiskConfig  $config
     */
    public function createS3Adapter(array $config): FilesystemAdapter
    {
        $config['version'] = 'latest';

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        $client = new S3Client($config);

        return new S3Filesystem($client, $config['bucket'], $config['prefix'] ?? '', $config['options'] ?? []);
    }

    /**
     * Get the default backup driver name.
     */
    public function getDefaultAdapter(): string
    {
        return JsonValueGuard::string($this->config->get('backups.default'));
    }

    /**
     * Set the default session driver name.
     */
    public function setDefaultAdapter(string $name): void
    {
        $this->config->set('backups.default', $name);
    }

    /**
     * Unset the given adapter instances.
     *
     * @param  string|string[]  $adapter
     */
    public function forget(array|string $adapter): self
    {
        $adapters = is_string($adapter) ? [$adapter] : $adapter;
        foreach ($adapters as $adapterName) {
            unset($this->adapters[$adapterName]);
        }

        return $this;
    }

    /**
     * Register a custom adapter creator closure.
     */
    /** @param Closure(Application, BackupDiskConfig): FilesystemAdapter $callback */
    public function extend(string $adapter, Closure $callback): self
    {
        $this->customCreators[$adapter] = $callback;

        return $this;
    }

    /**
     * Gets a backup adapter.
     */
    protected function get(string $name): FilesystemAdapter
    {
        return $this->adapters[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given backup disk.
     */
    protected function resolve(string $name): FilesystemAdapter
    {
        $config = $this->getConfig($name);

        throw_if(empty($config['adapter']), InvalidArgumentException::class, "Backup disk [$name] does not have a configured adapter.");

        $adapter = $config['adapter'];

        if (isset($this->customCreators[$adapter])) {
            return $this->callCustomCreator($config);
        }

        return match ($adapter) {
            'wings' => $this->createWingsAdapter($config),
            's3' => $this->createS3Adapter($this->s3Config($config)),
            default => throw new InvalidArgumentException("Adapter [$adapter] is not supported."),
        };
    }

    /**
     * Calls a custom creator for a given adapter type.
     *
     * @param  BackupDiskConfig&array{adapter: string}  $config
     */
    protected function callCustomCreator(array $config): FilesystemAdapter
    {
        return $this->customCreators[$config['adapter']]($this->app, $config);
    }

    /**
     * Returns the configuration associated with a given backup type.
     *
     * @return BackupDiskConfig
     */
    protected function getConfig(string $name): array
    {
        $config = $this->config->get("backups.disks.$name") ?: [];
        JsonValueGuard::assertValue($config);
        if (! is_array($config)) {
            return [];
        }

        $adapter = $config['adapter'] ?? null;
        if (! is_string($adapter) || $adapter === '') {
            return [];
        }

        $normalized = ['adapter' => $adapter];
        foreach ($config as $key => $value) {
            if ($key === 'adapter') {
                continue;
            }

            if (in_array($key, ['key', 'secret', 'token', 'bucket', 'prefix'], true)) {
                if (is_string($value)) {
                    $normalized[$key] = $value;
                }

                continue;
            }

            if ($key === 'options') {
                if (is_array($value)) {
                    $options = [];
                    foreach ($value as $option => $optionValue) {
                        if (is_string($option)) {
                            $options[$option] = $optionValue;
                        }
                    }

                    $normalized['options'] = $options;
                }

                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param  BackupDiskConfig&array{adapter: string}  $config
     * @return S3BackupDiskConfig
     */
    private function s3Config(array $config): array
    {
        $bucket = $config['bucket'] ?? null;
        throw_if(! is_string($bucket) || $bucket === '', InvalidArgumentException::class, 'The S3 backup adapter requires a bucket.');

        return array_merge($config, ['bucket' => $bucket]);
    }
}
