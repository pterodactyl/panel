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
namespace Pterodactyl\Http\Controllers\Base;

use Auth;
use Session;

use Pterodactyl\Models\User;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class LanguageController extends Controller
{

    protected $languages = [
        'de' => 'Danish',
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'it' => 'Italian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'se' => 'Swedish',
        'zh' => 'Chinese',
    ];

    /**
     * Controller Constructor
     */
    public function __construct()
    {
        //
    }

    public function setLanguage(Request $request, $language)
    {
        if (array_key_exists($language, $this->languages)) {
            if (Auth::check()) {
                $user = User::findOrFail(Auth::user()->id);
                $user->language = $language;
                $user->save();
            }
            Session::set('applocale', $language);
        }
        return redirect()->back();
    }

}
