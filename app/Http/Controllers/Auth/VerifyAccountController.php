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
        $data = DB::table('verification_tokens')->select('user')->where('token', '=', $token)->first();
        if (!$data) {
            return response()->redirectTo('/');
        }
        $user = User::whereId($data->user)->first();
        if (!$user) {
            return response()->redirectTo('/');
        }
        User::whereId($user->id)->update(['verified' => true]);
        DB::table('verification_tokens')->where('user', '=', $user->id)->delete();
        return response()->redirectTo('/');
    }
}
