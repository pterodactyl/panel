<?php

namespace Pterodactyl\Extensions\Backups;

use Closure;
use Aws\S3\S3Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;
use Illuminate\Foundation\Application;
use League\Flysystem\FilesystemAdapter;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class BackupManager
{
    protected ConfigRepository $config;

    /**
     * The array of resolved backup drivers.
     */
    protected array $adapters = [];

    /**
     * The registered custom driver creators.
     */
    protected array $customCreators;

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

        if (empty($config['adapter'])) {
            throw new \InvalidArgumentException("Backup disk [$name] does not have a configured adapter.");
        }

        $adapter = $config['adapter'];

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($config);
        }

        $adapterMethod = 'create' . Str::studly($adapter) . 'Adapter';
        if (method_exists($this, $adapterMethod)) {
            $instance = $this->{$adapterMethod}($config);

            Assert::isInstanceOf($instance, FilesystemAdapter::class);

            return $instance;
        }

        throw new \InvalidArgumentException("Adapter [$adapter] is not supported.");
    }

    /**
     * Calls a custom creator for a given adapter type.
     */
    protected function callCustomCreator(array $config): mixed
    {
        return $this->customCreators[$config['adapter']]($this->app, $config);
    }

    /**
     * Creates a new Wings adapter.
     */
    public function createWingsAdapter(array $config): FilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }

    /**
     * Creates a new S3 adapter.
     */
    public function createS3Adapter(array $config): FilesystemAdapter
    {
        $config['version'] = 'latest';

        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        $client = new S3Client($config);

        return new S3Filesystem($client, $config['bucket'], $config['prefix'] ?? '', $config['options'] ?? []);
    }

    /**
     * Returns the configuration associated with a given backup type.
     */
    protected function getConfig(string $name): array
    {
        return $this->config->get("backups.disks.$name") ?: [];
    }

    /**
     * Get the default backup driver name.
     */
    public function getDefaultAdapter(): string
    {
        return $this->config->get('backups.default');
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
     * @param string|string[] $adapter
     */
    public function forget(array|string $adapter): self
    {
        foreach ((array) $adapter as $adapterName) {
            unset($this->adapters[$adapterName]);
        }

        return $this;
    }

    /**
     * Register a custom adapter creator closure.
     */
    public function extend(string $adapter, \Closure $callback): self
    {
        $this->customCreators[$adapter] = $callback;

        return $this;
    }
}
