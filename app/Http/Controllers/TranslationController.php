<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TranslationController extends Controller
{
    /**
     * Change session locale
     *
     * @param  Request  $request
     * @return Response
     */
    public function changeLocale(Request $request)
    {
        Session::put('locale', $request->inputLocale);

        return redirect()->back();
    }
}
