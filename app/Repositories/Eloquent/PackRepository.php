<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Pack;
use Webmozart\Assert\Assert;
use Pterodactyl\Repositories\Concerns\Searchable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class PackRepository extends EloquentRepository implements PackRepositoryInterface
{
    use Searchable;

    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Pack::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileArchives($id, $collection = false)
    {
        Assert::numeric($id, 'First argument passed to getFileArchives must be numeric, received %s.');
        Assert::boolean($collection, 'Second argument passed to getFileArchives must be boolean, received %s.');

        $pack = $this->getBuilder()->find($id, ['id', 'uuid']);
        if (! $pack) {
            throw new ModelNotFoundException;
        }

        $storage = $this->app->make(FilesystemFactory::class);
        $files = collect($storage->disk('default')->files('packs/' . $pack->uuid));

        $files = $files->map(function ($file) {
            $path = storage_path('app/' . $file);

            return (object) [
                'name' => basename($file),
                'hash' => sha1_file($path),
                'size' => human_readable($path),
            ];
        });

        return ($collection) ? $files : (object) $files->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getWithServers($id)
    {
        Assert::numeric($id, 'First argument passed to getWithServers must be numeric, received %s.');

        $instance = $this->getBuilder()->with('servers.node', 'servers.user')->find($id, $this->getColumns());
        if (! $instance) {
            throw new ModelNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithEggAndServerCount($paginate = 50)
    {
        Assert::integer($paginate, 'First argument passed to paginateWithOptionAndServerCount must be integer, received %s.');

        return $this->getBuilder()->with('egg')->withCount('servers')
            ->search($this->searchTerm)
            ->paginate($paginate, $this->getColumns());
    }
}
