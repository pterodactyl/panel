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

namespace Pterodactyl\Http\Controllers\Admin;

use DB;
use Log;
use Alert;
use Storage;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\ServiceRepository\Pack;
use Pterodactyl\Exceptions\DisplayValidationException;

class PackController extends Controller
{
    public function __construct()
    {
        //
    }

    public function listAll(Request $request)
    {
        return view('admin.services.packs.index', ['services' => Models\Service::all()]);
    }

    public function listByOption(Request $request, $id)
    {
        return view('admin.services.packs.byoption', [
            'option' => Models\ServiceOptions::with('service', 'packs')->findOrFail($id)
        ]);
    }

    public function listByService(Request $request, $id)
    {
        return view('admin.services.packs.byservice', [
            'service' => Models\Service::with('options', 'options.packs')->findOrFail($id),
        ]);
    }

    public function new(Request $request, $opt = null)
    {
        return view('admin.services.packs.new', [
            'services' => Models\Service::with('options')->get(),
        ]);
    }

    public function create(Request $request)
    {
        try {
            $repo = new Pack;
            $pack = $repo->create($request->only([
                'name',
                'version',
                'description',
                'option',
                'selectable',
                'visible',
                'file_upload',
            ]));
            Alert::success('Successfully created new service!')->flash();

            return redirect()->route('admin.services.packs.edit', $pack->id)->withInput();
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
        $pack = Models\ServicePack::with('option.service')->findOrFail($id);

        return view('admin.services.packs.edit', [
            'pack' => $pack,
            'services' => Models\Service::all()->load('options'),
            'files' => Storage::files('packs/' . $pack->uuid),
        ]);
    }

    public function update(Request $request, $id)
    {
        if (! is_null($request->input('action_delete'))) {
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
                $repo->update($id, $request->only([
                    'name',
                    'version',
                    'description',
                    'option',
                    'selectable',
                    'visible',
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
        ];

        $filename = tempnam(sys_get_temp_dir(), 'pterodactyl_');
        if ((bool) $files) {
            $zip = new \ZipArchive;
            if (! $zip->open($filename, \ZipArchive::CREATE)) {
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
                'Content-Type' => 'application/json',
            ])->deleteFileAfterSend(true);
        }
    }

    public function uploadForm(Request $request, $for = null)
    {
        return view('admin.services.packs.upload', [
            'services' => Models\Service::all()->load('options'),
        ]);
    }

    public function postUpload(Request $request)
    {
        try {
            $repo = new Pack;
            $pack = $repo->createWithTemplate($request->only(['option', 'file_upload']));
            Alert::success('Successfully created new service!')->flash();

            return redirect()->route('admin.services.packs.edit', $pack->id)->withInput();
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
