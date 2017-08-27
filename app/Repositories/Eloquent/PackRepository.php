<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
    public function paginateWithOptionAndServerCount($paginate = 50)
    {
        Assert::integer($paginate, 'First argument passed to paginateWithOptionAndServerCount must be integer, received %s.');

        return $this->getBuilder()->with('option')->withCount('servers')
            ->search($this->searchTerm)
            ->paginate($paginate, $this->getColumns());
    }
}
