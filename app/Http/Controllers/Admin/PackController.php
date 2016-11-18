<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use DB;
use Log;
use Storage;

use Pterodactyl\Models;
use Pterodactyl\Repositories\ServiceRepository\Pack;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

use Illuminate\Http\Request;

class PackController extends Controller
{
    public function __construct()
    {
        //
    }

    protected function formatServices()
    {
        $options = Models\ServiceOptions::select(
            'services.name AS p_service',
            'service_options.id',
            'service_options.name'
        )->join('services', 'services.id', '=', 'service_options.parent_service')->get();

        $array = [];
        foreach($options as &$option) {
            if (!array_key_exists($option->p_service, $array)) {
                $array[$option->p_service] = [];
            }

            $array[$option->p_service] = array_merge($array[$option->p_service], [[
                'id' => $option->id,
                'name' => $option->name
            ]]);
        }

        return $array;
    }

    public function listAll(Request $request)
    {
        return view('admin.services.packs.index', [
            'services' => Models\Service::all()
        ]);
    }

    public function listByOption(Request $request, $id)
    {
        $option = Models\ServiceOptions::findOrFail($id);
        return view('admin.services.packs.byoption', [
            'packs' => Models\ServicePack::where('option', $option->id)->get(),
            'service' => Models\Service::findOrFail($option->parent_service),
            'option' => $option
        ]);
    }

    public function listByService(Request $request, $id)
    {
        return view('admin.services.packs.byservice', [
            'service' => Models\Service::findOrFail($id),
            'options' => Models\ServiceOptions::select(
                'service_options.id',
                'service_options.name',
                DB::raw('(SELECT COUNT(id) FROM service_packs WHERE service_packs.option = service_options.id) AS p_count')
            )->where('parent_service', $id)->get()
        ]);
    }

    public function new(Request $request, $opt = null)
    {
        return view('admin.services.packs.new', [
            'services' => $this->formatServices(),
            'packFor' => $opt,
        ]);
    }

    public function create(Request $request)
    {
        try {
            $repo = new Pack;
            $id = $repo->create($request->except([
                '_token'
            ]));
            Alert::success('Successfully created new service!')->flash();
            return redirect()->route('admin.services.packs.edit', $id)->withInput();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.packs.new', $request->input('option'))->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new service pack.')->flash();
        }
        return redirect()->route('admin.services.packs.new', $request->input('option'))->withInput();
    }

    public function edit(Request $request, $id)
    {
        $pack = Models\ServicePack::findOrFail($id);
        $option = Models\ServiceOptions::select('id', 'parent_service', 'name')->where('id', $pack->option)->first();
        return view('admin.services.packs.edit', [
            'pack' => $pack,
            'services' => $this->formatServices(),
            'files' => Storage::files('packs/' . $pack->uuid),
            'service' => Models\Service::findOrFail($option->parent_service),
            'option' => $option
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!is_null($request->input('action_delete'))) {
            try {
                $repo = new Pack;
                $repo->delete($id);
                Alert::success('The requested service pack has been deleted from the system.')->flash();
                return redirect()->route('admin.services.packs');
            } catch (DisplayException $ex) {
                Alert::danger($ex->getMessage())->flash();
            } catch (\Exception $ex) {
                Log::error($ex);
                Alert::danger('An error occured while attempting to delete this pack.')->flash();
            }
            return redirect()->route('admin.services.packs.edit', $id);
        } else {
            try {
                $repo = new Pack;
                $repo->update($id, $request->except([
                    '_token'
                ]));
                Alert::success('Service pack has been successfully updated.')->flash();
            } catch (DisplayValidationException $ex) {
                return redirect()->route('admin.services.packs.edit', $id)->withErrors(json_decode($ex->getMessage()))->withInput();
            } catch (\Exception $ex) {
                Log::error($ex);
                Alert::danger('An error occured while attempting to add edit this pack.')->flash();
            }
            return redirect()->route('admin.services.packs.edit', $id);
        }
    }

    public function export(Request $request, $id, $files = false)
    {
        $pack = Models\ServicePack::findOrFail($id);
        $json = [
            'name' => $pack->name,
            'version' => $pack->version,
            'description' => $pack->dscription,
            'selectable' => (bool) $pack->selectable,
            'visible' => (bool) $pack->visible,
            'build' => [
                'memory' => $pack->build_memory,
                'swap' => $pack->build_swap,
                'cpu' => $pack->build_cpu,
                'io' => $pack->build_io,
                'container' => $pack->build_container,
                'script' => $pack->build_script
            ]
        ];

        $filename = tempnam(sys_get_temp_dir(), 'pterodactyl_');
        if ((bool) $files) {
            $zip = new \ZipArchive;
            if (!$zip->open($filename, \ZipArchive::CREATE)) {
                abort(503, 'Unable to open file for writing.');
            }

            $files = Storage::files('packs/' . $pack->uuid);
            foreach ($files as $file) {
                $zip->addFile(storage_path('app/' . $file), basename(storage_path('app/' . $file)));
            }

            $zip->addFromString('import.json', json_encode($json, JSON_PRETTY_PRINT));
            $zip->close();

            return response()->download($filename, 'pack-' . $pack->name . '.zip')->deleteFileAfterSend(true);
        } else {
            $fp = fopen($filename, 'a+');
            fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
            fclose($fp);
            return response()->download($filename, 'pack-' . $pack->name . '.json', [
                'Content-Type' => 'application/json'
            ])->deleteFileAfterSend(true);
        }
    }

    public function uploadForm(Request $request, $for = null) {
        return view('admin.services.packs.upload', [
            'services' => $this->formatServices(),
            'for' => $for
        ]);
    }

    public function postUpload(Request $request)
    {
        try {
            $repo = new Pack;
            $id = $repo->createWithTemplate($request->except([
                '_token'
            ]));
            Alert::success('Successfully created new service!')->flash();
            return redirect()->route('admin.services.packs.edit', $id)->withInput();
        } catch (DisplayValidationException $ex) {
            return redirect()->back()->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new service pack.')->flash();
        }
        return redirect()->back();
    }
}
