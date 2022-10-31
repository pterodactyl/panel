<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;

class VerifyAccountController extends Controller
{
    public function index(string $token): RedirectResponse
    {
        $data = DB::table('verification_tokens')->select('user')->where('token', $token)->first()->user;
        if (!$data) {
            return response()->redirectTo('/');
        }

        $user = User::whereId($data)->first()->id;
        if (!$user) {
            return response()->redirectTo('/');
        }

        User::whereId($user)->update(['verified' => true]);
        DB::table('verification_tokens')->where('user', $user)->delete();

        return response()->redirectTo('/');
    }
}
