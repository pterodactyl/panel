<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class DiscordController extends Controller
{
    private UserCreationService $creationService;
    private SettingsRepositoryInterface $settings;

    public function __construct(UserCreationService $creationService, SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
        $this->creationService = $creationService;
    }

    /**
     * Uses the Discord API to return a user objext.
     */
    public function index()
    {
        return redirect(
            'https://discord.com/api/oauth2/authorize?'
            . 'client_id=' . $this->settings->get('jexactyl::discord:id')
            . '&redirect_uri=' . route('auth.discord.callback')
            . '&response_type=code&scope=identify%20email%20guilds%20guilds.join&prompt=none',
        );
    }

    /**
     * Returns data from the Discord API to login.
     *
     * @throws DisplayException
     * @throws DataValidationException
     */
    public function callback(Request $request)
    {
        $code = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => $this->settings->get('jexactyl::discord:id'),
            'client_secret' => $this->settings->get('jexactyl::discord:secret'),
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'redirect_uri' => route('auth.discord.callback'),
        ])->body();

        $discord = json_decode(Http::withHeaders(['Authorization' => 'Bearer ' . $code->access_token])->asForm()->get('https://discord.com/api/users/@me')->body());

        if (!$code->ok() || !$discord->ok()) return;
        if (preg_match('(email|guilds|identify|guilds.join)', $code->scope) !== 1) return;

        if (User::where('discord_id', $discord->id)->exists()) {
            $user = User::where('discord_id', $discord->id)->first();
            Auth::loginUsingId($user->id, true);

            return redirect()->route('index');
        } else {
            if ($this->settings->get('jexactyl::discord:enabled') == 'false') return;

            $approved = true;
            if ($this->settings->get('jexactyl::approvals:enabled') == 'true') {
                $approved = false;
            };

            if (User::where('email', $discord->email)->exists()) {
                redirect()->route('auth.login');
                throw new DisplayException('An account with this email already exists.');
            }

            $data = [
                'approved' => $approved,
                'email' => $discord->email,
                'username' => $discord->id,
                'discord_id' => $discord->id,
                'name_first' => $discord->username,
                'name_last' => '#' . $discord->discriminator,
                'password' => $this->generatePassword(),
                'ip' => $request->getClientIp(),
                'store_cpu' => $this->settings->get('jexactyl::registration:cpu'),
                'store_memory' => $this->settings->get('jexactyl::registration:memory'),
                'store_disk' => $this->settings->get('jexactyl::registration:disk'),
                'store_slots' => $this->settings->get('jexactyl::registration:slot'),
                'store_ports' => $this->settings->get('jexactyl::registration:port'),
                'store_backups' => $this->settings->get('jexactyl::registration:backup'),
                'store_databases' => $this->settings->get('jexactyl::registration:database'),
            ];

            $this->creationService->handle($data);
            Auth::loginUsingId(User::where('discord_id', $discord->id)->first()->discord_id, true);

            return redirect()->route('index');
        }
    }

    /**
     * Returns a string used for creating a user's password on the Panel.
     */
    private function generatePassword(): string
    {
        $chars = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($chars), 0, 16);
    }
}
