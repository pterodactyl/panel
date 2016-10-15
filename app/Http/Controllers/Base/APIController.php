<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
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
namespace Pterodactyl\Http\Controllers\Base;

use Alert;

use Pterodactyl\Models;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;

use Illuminate\Http\Request;

class APIController extends Controller
{
    public function index(Request $request)
    {
        $keys = Models\APIKey::where('user', $request->user()->id)->get();
        foreach($keys as &$key) {
            $key->permissions = Models\APIPermission::where('key_id', $key->id)->get();
        }

        return view('base.api.index', [
            'keys' => $keys
        ]);

    }

    public function new(Request $request)
    {
        return view('base.api.new');
    }

    public function save(Request $request)
    {

    }
}
