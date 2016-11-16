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

    public function list(Request $request, $id)
    {
        $option = Models\ServiceOptions::findOrFail($id);
        return view('admin.services.packs.index', [
            'packs' => Models\ServicePack::where('option', $option->id)->get(),
            'service' => Models\Service::findOrFail($option->parent_service),
            'option' => $option
        ]);
    }

    public function new(Request $request, $opt = null)
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

        return view('admin.services.packs.new', [
            'services' => $array,
            'packFor' => $opt,
        ]);
    }

    public function create(Request $request)
    {
        // dd($request->all());
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
        dd($pack, Storage::url('packs/' . $pack->uuid));
    }
}
