<?php
/**
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

namespace Pterodactyl\Http\Controllers\Daemon;

use Illuminate\Http\Request;
use Pterodactyl\Models\Service;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Http\Controllers\Controller;

class ServiceController extends Controller
{
    /**
     * Returns a listing of all services currently on the system,
     * as well as the associated files and the file hashes for
     * caching purposes.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $response = [];
        foreach (Service::all() as $service) {
            $response[$service->folder] = [
                'main.json' => sha1($this->getConfiguration($service->id)->toJson()),
                'index.js' => sha1($service->index_file),
            ];
        }

        return response()->json($response);
    }

    /**
     * Returns the contents of the requested file for the given service.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  string                     $folder
     * @param  string                     $file
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\FileResponse
     */
    public function pull(Request $request, $folder, $file)
    {
        $service = Service::where('folder', $folder)->firstOrFail();

        if ($file === 'index.js') {
            return response($service->index_file)->header('Content-Type', 'text/plain');
        } else if ($file === 'main.json') {
            return response()->json($this->getConfiguration($service->id));
        }

        return abort(404);
    }

    /**
     * Returns a `main.json` file based on the configuration
     * of each service option.
     *
     * @param  int     $id
     * @return \Illuminate\Support\Collection
     */
    protected function getConfiguration($id)
    {
        $options = ServiceOption::where('service_id', $id)->get();

        return $options->mapWithKeys(function ($item) use ($options) {
            return [
                $item->tag => array_filter([
                    'symlink' => $options->where('id', $item->config_from)->pluck('tag')->pop(),
                    'startup' => json_decode($item->config_startup),
                    'stop' => $item->config_stop,
                    'configs' => json_decode($item->config_files),
                    'log' => json_decode($item->config_logs),
                    'query' => 'none',
                ]),
            ];
        });
    }
}
