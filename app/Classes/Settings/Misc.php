<?php

namespace App\Classes\Settings;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class Misc
{
    public function __construct()
    {

    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon' => 'nullable|max:10000|mimes:jpg,png,jpeg',
            'favicon' => 'nullable|max:10000|mimes:ico',
            'discord-bot-token' => 'nullable|string',
            'discord-client-id' => 'nullable|string',
            'discord-client-secret' => 'nullable|string',
            'discord-guild-id' => 'nullable|string',
            'discord-invite-url' => 'nullable|string',
            'discord-role-id' => 'nullable|string',
            'recaptcha-site-key' => 'nullable|string',
            'recaptcha-secret-key' => 'nullable|string',
            'enable-recaptcha' => 'nullable|string',
            'mailservice' => 'nullable|string',
            'mailhost' => 'nullable|string',
            'mailport' => 'nullable|string',
            'mailusername' => 'nullable|string',
            'mailpassword' => 'nullable|string',
            'mailencryption' => 'nullable|string',
            'mailfromadress' => 'nullable|string',
            'mailfromname' => 'nullable|string',
            'enable_referral' => 'nullable|string',
            'referral_reward' => 'nullable|numeric',
            'referral_allowed' => 'nullable|string',
            'always_give_commission' => 'nullable|string',
            'referral_percentage' => 'nullable|numeric',
            'referral_mode' => 'nullable|string',
            'ticket_enabled' => 'nullable|string',
            'ticket_notify' => 'string',
        ]);

        $validator->after(function ($validator) use ($request) {
            // if enable-recaptcha is true then recaptcha-site-key and recaptcha-secret-key must be set
            if ($request->get('enable-recaptcha') == 'true' && (! $request->get('recaptcha-site-key') || ! $request->get('recaptcha-secret-key'))) {
                $validator->errors()->add('recaptcha-site-key', 'The site key is required if recaptcha is enabled.');
                $validator->errors()->add('recaptcha-secret-key', 'The secret key is required if recaptcha is enabled.');
            }
        });

        if ($validator->fails()) {
            return redirect(route('admin.settings.index').'#misc')->with('error', __('Misc settings have not been updated!'))->withErrors($validator)
                ->withInput();
        }

        if ($request->hasFile('icon')) {
            $request->file('icon')->storeAs('public', 'icon.png');
        }
        if ($request->hasFile('favicon')) {
            $request->file('favicon')->storeAs('public', 'favicon.ico');
        }

        $values = [
            'SETTINGS::DISCORD:BOT_TOKEN' => 'discord-bot-token',
            'SETTINGS::DISCORD:CLIENT_ID' => 'discord-client-id',
            'SETTINGS::DISCORD:CLIENT_SECRET' => 'discord-client-secret',
            'SETTINGS::DISCORD:GUILD_ID' => 'discord-guild-id',
            'SETTINGS::DISCORD:INVITE_URL' => 'discord-invite-url',
            'SETTINGS::DISCORD:ROLE_ID' => 'discord-role-id',
            'SETTINGS::RECAPTCHA:SITE_KEY' => 'recaptcha-site-key',
            'SETTINGS::RECAPTCHA:SECRET_KEY' => 'recaptcha-secret-key',
            'SETTINGS::RECAPTCHA:ENABLED' => 'enable-recaptcha',
            'SETTINGS::MAIL:MAILER' => 'mailservice',
            'SETTINGS::MAIL:HOST' => 'mailhost',
            'SETTINGS::MAIL:PORT' => 'mailport',
            'SETTINGS::MAIL:USERNAME' => 'mailusername',
            'SETTINGS::MAIL:PASSWORD' => 'mailpassword',
            'SETTINGS::MAIL:ENCRYPTION' => 'mailencryption',
            'SETTINGS::MAIL:FROM_ADDRESS' => 'mailfromadress',
            'SETTINGS::MAIL:FROM_NAME' => 'mailfromname',
            'SETTINGS::REFERRAL::ENABLED' => 'enable_referral',
            'SETTINGS::REFERRAL::REWARD' => 'referral_reward',
            'SETTINGS::REFERRAL::ALLOWED' => 'referral_allowed',
            'SETTINGS::REFERRAL:MODE' => 'referral_mode',
            'SETTINGS::REFERRAL::ALWAYS_GIVE_COMMISSION' => 'always_give_commission',
            'SETTINGS::REFERRAL:PERCENTAGE' => 'referral_percentage',
            'SETTINGS::TICKET:ENABLED' => 'ticket_enabled',
            'SETTINGS::TICKET:NOTIFY' => 'ticket_notify',

        ];

        foreach ($values as $key => $value) {
            $param = $request->get($value);

            Settings::where('key', $key)->updateOrCreate(['key' => $key], ['value' => $param]);
            Cache::forget('setting'.':'.$key);
        }

        return redirect(route('admin.settings.index').'#misc')->with('success', __('Misc settings updated!'));
    }
}
