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

use Log;
use Alert;
use Storage;
use Illuminate\Http\Request;
use Pterodactyl\Models\Pack;
use Pterodactyl\Models\Service;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\PackRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class PackController extends Controller
{
    /**
     * Display listing of all packs on the system.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $packs = Pack::with('option')->withCount('servers');

        if (! is_null($request->input('query'))) {
            $packs->search($request->input('query'));
        }

        return view('admin.packs.index', ['packs' => $packs->paginate(50)]);
    }

    /**
     * Display new pack creation form.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function new(Request $request)
    {
        return view('admin.packs.new', [
            'services' => Service::with('options')->get(),
        ]);
    }

    /**
     * Display new pack creation modal for use with template upload.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function newTemplate(Request $request)
    {
        return view('admin.packs.modal', [
            'services' => Service::with('options')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $repo = new PackRepository;

        try {
            if ($request->input('action') === 'from_template') {
                $pack = $repo->createWithTemplate($request->intersect(['option_id', 'file_upload']));
            } else {
                $pack = $repo->create($request->intersect([
                    'name', 'description', 'version', 'option_id',
                    'selectable', 'visible', 'locked', 'file_upload',
                ]));
            }
            Alert::success('Pack successfully created on the system.')->flash();

            return redirect()->route('admin.packs.view', $pack->id);
        } catch(DisplayValidationException $ex) {
            return redirect()->route('admin.packs.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new service pack. This error has been logged.')->flash();
        }

        return redirect()->route('admin.packs.new')->withInput();
    }


    // public function export(Request $request, $id, $files = false)
    // {
    //     $pack = Models\Pack::findOrFail($id);
    //     $json = [
    //         'name' => $pack->name,
    //         'version' => $pack->version,
    //         'description' => $pack->dscription,
    //         'selectable' => (bool) $pack->selectable,
    //         'visible' => (bool) $pack->visible,
    //     ];

    //     $filename = tempnam(sys_get_temp_dir(), 'pterodactyl_');
    //     if ((bool) $files) {
    //         $zip = new \ZipArchive;
    //         if (! $zip->open($filename, \ZipArchive::CREATE)) {
    //             abort(503, 'Unable to open file for writing.');
    //         }

    //         $files = Storage::files('packs/' . $pack->uuid);
    //         foreach ($files as $file) {
    //             $zip->addFile(storage_path('app/' . $file), basename(storage_path('app/' . $file)));
    //         }

    //         $zip->addFromString('import.json', json_encode($json, JSON_PRETTY_PRINT));
    //         $zip->close();

    //         return response()->download($filename, 'pack-' . $pack->name . '.zip')->deleteFileAfterSend(true);
    //     } else {
    //         $fp = fopen($filename, 'a+');
    //         fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
    //         fclose($fp);

    //         return response()->download($filename, 'pack-' . $pack->name . '.json', [
    //             'Content-Type' => 'application/json',
    //         ])->deleteFileAfterSend(true);
    //     }
    // }

}
