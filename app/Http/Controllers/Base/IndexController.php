<?php

namespace Pterodactyl\Http\Controllers\Base;

use Auth;
use Hash;
use Google2FA;
use Alert;

use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;

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

        $user = $request->user();

        $user->totp_secret = Google2FA::generateSecretKey();
        $user->save();

        return response()->json([
            'qrImage' => Google2FA::getQRCodeGoogleUrl(
                'Pterodactyl',
                $user->email,
                $user->totp_secret
            ),
            'secret' => $user->totp_secret
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
            return response(null, 500);
        }

        $user = $request->user();
        if($user->toggleTotp($request->input('token'))) {
            return response('true');
        }

        return response('false');

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

        $user = $request->user();
        if($user->toggleTotp($request->input('token'))) {
            return redirect()->route('account.totp');
        }

        Alert::danger('The TOTP token provided was invalid.')->flash();
        return redirect()->route('account.totp');

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

        $user = $request->user();

        if (!password_verify($request->input('password'), $user->password)) {
            Alert::danger('The password provided was not valid for this account.')->flash();
            return redirect()->route('account');
        }

        $user->email = $request->input('new_email');
        $user->save();

        Alert::success('Your email address has successfully been updated.')->flash();
        return redirect()->route('account');

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

        $user = $request->user();

        if (!password_verify($request->input('current_password'), $user->password)) {
            Alert::danger('The password provided was not valid for this account.')->flash();
            return redirect()->route('account');
        }

        try {
            $user->setPassword($request->input('new_password'));
            Alert::success('Your password has successfully been updated.')->flash();
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        }

        return redirect()->route('account');

    }

}
