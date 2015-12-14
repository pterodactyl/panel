<?php

namespace Pterodactyl\Http\Controllers\Base;

use Auth;
use Debugbar;
use Google2FA;
use Log;
use Alert;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {

        // All routes in this controller are protected by the authentication middleware.
        $this->middleware('auth');
    }

    /**
     * Returns listing of user's servers.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getIndex(Request $request)
    {
        return view('base.index', [
            'servers' => Server::getUserServers(),
        ]);
    }

    /**
     * Generate a random string.
     *
     * @param  \Illuminate\Http\Request $request
     * @return string
     */
    public function getPassword(Request $request, $length = 16)
    {
        $length = ($length < 8) ? 8 : $length;
        return str_random($length);
    }

    /**
     * Returns TOTP Management Page.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getAccountTotp(Request $request)
    {
        return view('base.totp');
    }

    /**
     * Generates TOTP Secret and returns popup data for user to verify
     * that they can generate a valid response.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function putAccountTotp(Request $request)
    {

        try {
            $totpSecret = User::setTotpSecret(Auth::user()->id);
        } catch (\Exception $e) {
            if ($e instanceof AccountNotFoundException) {
                return response($e->getMessage(), 500);
            }
            throw $e;
        }

        return response()->json([
            'qrImage' => Google2FA::getQRCodeGoogleUrl(
                'Pterodactyl',
                Auth::user()->email,
                $totpSecret
            ),
            'secret' => $totpSecret
        ]);

    }

    /**
     * Verifies that 2FA token recieved is valid and will work on the account.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postAccountTotp(Request $request)
    {

        if (!$request->has('token')) {
            return response('No input \'token\' defined.', 500);
        }

        try {
            if(User::toggleTotp(Auth::user()->id, $request->input('token'))) {
                return response('true');
            }
            return response('false');
        } catch (\Exception $e) {
            if ($e instanceof AccountNotFoundException) {
                return response($e->getMessage(), 500);
            }
            throw $e;
        }

    }

    /**
     * Disables TOTP on an account.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function deleteAccountTotp(Request $request)
    {

        if (!$request->has('token')) {
            Alert::danger('Missing required `token` field in request.')->flash();
            return redirect()->route('account.totp');
        }

        try {
            if(User::toggleTotp(Auth::user()->id, $request->input('token'))) {
                return redirect()->route('account.totp');
            }

            Alert::danger('Unable to disable TOTP on this account, was the token correct?')->flash();
            return redirect()->route('account.totp');
        } catch (\Exception $e) {
            if ($e instanceof AccountNotFoundException) {
                Alert::danger('An error occured while attempting to perform this action.')->flash();
                return redirect()->route('account.totp');
            }
            throw $e;
        }

    }

    /**
     * Display base account information page.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getAccount(Request $request)
    {
        return view('base.account');
    }

    /**
     * Update an account email.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postAccountEmail(Request $request)
    {

        $this->validate($request, [
            'new_email' => 'required|email',
            'password' => 'required'
        ]);

        if (!password_verify($request->input('password'), Auth::user()->password)) {
            Alert::danger('The password provided was not valid for this account.')->flash();
            return redirect()->route('account');
        }

        // Met Validation, lets roll out.
        try {
            User::setEmail(Auth::user()->id, $request->input('new_email'));
            Alert::success('Your email address has successfully been updated.')->flash();
            return redirect()->route('account');
        } catch (\Exception $e) {
            if ($e instanceof AccountNotFoundException || $e instanceof DisplayException) {
                Alert::danger($e->getMessage())->flash();
                return redirect()->route('account');
            }
            throw $e;
        }
    }

    /**
     * Update an account password.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postAccountPassword(Request $request)
    {

        $this->validate($request, [
            'current_password' => 'required',
            'new_password' => 'required|confirmed|different:current_password|regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})',
            'new_password_confirmation' => 'required'
        ]);

        if (!password_verify($request->input('current_password'), Auth::user()->password)) {
            Alert::danger('The password provided was not valid for this account.')->flash();
            return redirect()->route('account');
        }

        // Met Validation, lets roll out.
        try {
            User::setPassword(Auth::user()->id, $request->input('new_password'));
            Alert::success('Your password has successfully been updated.')->flash();
            return redirect()->route('account');
        } catch (\Exception $e) {
            if ($e instanceof AccountNotFoundException || $e instanceof DisplayException) {
                Alert::danger($e->getMessage())->flash();
                return redirect()->route('account');
            }
            throw $e;
        }

    }

}
