<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Pterodactyl\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Pterodactyl\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Illuminate\Contracts\Container\BindingResolutionException;

class DiscordController extends Controller
{
    private AuthManager $auth;
    private SettingsRepositoryInterface $settings;
    private UserCreationService $creationService;

    public function __construct(
        UserCreationService $creationService,
    )
    {
        $this->auth = Container::getInstance()->make(AuthManager::class);
        $this->creationService = $creationService;
    }

    /**
     * Uses the Discord API to return a user objext.
     */
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'https://discord.com/api/oauth2/authorize?'
            .'client_id='.$this->settings->get('jexactyl::discord:id')
            .'&redirect_uri='.$this->settings->get('jexactyl::discord:redirect')
            .'&response_type=code&scope=identify%20email%20guilds%20guilds.join&prompt=none'
        ], 200, [], null, false);
    }

    /**
     * Returns data from the Discord API to login.
     * 
     * @throws DisplayException
     * @throws DataValidationException
     */
    public function authenticate(Request $request)
    {
        $code = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => $this->settings->get('jexactyl::discord:id'),
            'client_secret' => $this->settings->get('jexactyl::discord:secret'),
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'redirect_uri' => $this->settings->get('jexactyl::discord:redirect'),
        ]);

        if (!$code->ok()) return;

        $req = json_decode($code->body());
        if (preg_match("(email|guilds|identify|guilds.join)", $req->scope) !== 1) return;

        $discord = json_decode(Http::withHeaders(["Authorization" => "Bearer ".$req->access_token])->asForm()->get('https://discord.com/api/users/@me')->body());

        if (User::where('email', $discord->email)->exists()) {
            $user = User::where('email', $discord->email)->get();
            Auth::loginUsingId($user->id, true);

            return redirect('/');
        } else {
            if ($this->settings->get('jexactyl::registration:enabled') != true) return;

            $username = $this->genString();
            $data = [
                'email' => $discord->email,
                'username' => $username,
                'name_first' => $discord->username,
                'name_last' => $discord->discriminator,
                'password' => $this->genString(),
                'ip' => $request->getClientIp(),
                'store_cpu' => $this->settings->get('jexactyl::registration:cpu', 0),
                'store_memory' => $this->settings->get('jexactyl::registration:memory', 0),
                'store_disk' => $this->settings->get('jexactyl::registration:disk', 0),
                'store_slots' => $this->settings->get('jexactyl::registration:slot', 0),
                'store_ports' => $this->settings->get('jexactyl::registration:port', 0),
                'store_backups' => $this->settings->get('jexactyl::registration:backup', 0),
                'store_databases' => $this->settings->get('jexactyl::registration:database', 0),
            ];

            try {
                $this->creationService->handle($data);
            } catch (Exception $e) { return; }

            $user = User::where('username', $username)->get();

            Auth::loginUsingId($user->id, true);

            return redirect('/');
        }
    }

    /**
     * Returns a string used for creating a users
     * username and password on the Panel.
     */
    public function genString(): string
    {
        $chars = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        return substr(str_shuffle($chars), 0, 16);
    }
}