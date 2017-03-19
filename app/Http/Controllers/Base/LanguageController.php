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

namespace Pterodactyl\Http\Controllers\Base;

use Auth;
use Session;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Controllers\Controller;

class LanguageController extends Controller
{
    /**
     * A list of supported languages on the panel.
     *
     * @var array
     */
    protected $languages = [
        'de' => 'German',
        'en' => 'English',
        'et' => 'Estonian',
        'nb' => 'Norwegian',
        'nl' => 'Dutch',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
    ];

    /**
     * Sets the language for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $language
     * @return \Illuminate\Http\RedirectResponse
     */
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
