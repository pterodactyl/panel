<?php

namespace App\Classes\Settings;

use App\Classes\Pterodactyl;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Qirolab\Theme\Theme;


class System
{
    public function __construct()
    {

    }

    public function checkPteroClientkey()
    {
        $response = Pterodactyl::getClientUser();

        if ($response->failed()) {
            return redirect()->back()->with('error', __('Your Key or URL is not correct'));
        }

        return redirect()->back()->with('success', __('Everything is good!'));
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'register-ip-check' => 'string',
            'server-create-charge-first-hour' => 'string',
            'credits-display-name' => 'required|string',
            'allocation-limit' => 'required|min:0|integer',
            'force-email-verification' => 'string',
            'force-discord-verification' => 'string',
            'initial-credits' => 'required|min:0|integer',
            'initial-server-limit' => 'required|min:0|integer',
            'credits-reward-amount-discord' => 'required|min:0|integer',
            'credits-reward-amount-email' => 'required|min:0|integer',
            'server-limit-discord' => 'required|min:0|integer',
            'server-limit-email' => 'required|min:0|integer',
            'server-limit-purchase' => 'required|min:0|integer',
            'pterodactyl-api-key' => 'required|string',
            'pterodactyl-url' => 'required|string',
            'per-page-limit' => 'required|min:0|integer',
            'pterodactyl-admin-api-key' => 'required|string',
            'enable-upgrades' => 'string',
            'enable-disable-servers' => 'string',
            'enable-disable-new-users' => 'string',
            'show-imprint' => 'string',
            'show-privacy' => 'string',
            'show-tos' => 'string',
            'alert-enabled' => 'string',
            'alter-type' => 'string',
            'alert-message' => 'string|nullable',
            'motd-enabled' => 'string',
            'usefullinks-enabled' => 'string',
            'motd-message' => 'string|nullable',
            'seo-title' => 'string|nullable',
            'seo-description' => 'string|nullable',
        ]);

        $validator->after(function ($validator) use ($request) {
            // if enable-recaptcha is true then recaptcha-site-key and recaptcha-secret-key must be set
            if ($request->get('enable-upgrades') == 'true' && (! $request->get('pterodactyl-admin-api-key'))) {
                $validator->errors()->add('pterodactyl-admin-api-key', 'The admin api key is required when upgrades are enabled.');
            }
        });

        if ($validator->fails()) {
            return redirect(route('admin.settings.index').'#system')->with('error', __('System settings have not been updated!'))->withErrors($validator)
                ->withInput();
        }

        // update Icons from request
        $this->updateIcons($request);

        $values = [

            "SETTINGS::SYSTEM:REGISTER_IP_CHECK" => "register-ip-check",
            "SETTINGS::SYSTEM:SERVER_CREATE_CHARGE_FIRST_HOUR" => "server-create-charge-first-hour",
            "SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME" => "credits-display-name",
            "SETTINGS::SERVER:ALLOCATION_LIMIT" => "allocation-limit",
            "SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER" => "minimum-credits",
            "SETTINGS::USER:FORCE_DISCORD_VERIFICATION" => "force-discord-verification",
            "SETTINGS::USER:FORCE_EMAIL_VERIFICATION" => "force-email-verification",
            "SETTINGS::USER:INITIAL_CREDITS" => "initial-credits",
            "SETTINGS::USER:INITIAL_SERVER_LIMIT" => "initial-server-limit",
            "SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_DISCORD" => "credits-reward-amount-discord",
            "SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL" => "credits-reward-amount-email",
            "SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD" => "server-limit-discord",
            "SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL" => "server-limit-email",
            "SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE" => "server-limit-purchase",
            "SETTINGS::MISC:PHPMYADMIN:URL" => "phpmyadmin-url",
            "SETTINGS::SYSTEM:PTERODACTYL:URL" => "pterodactyl-url",
            'SETTINGS::SYSTEM:PTERODACTYL:PER_PAGE_LIMIT' => "per-page-limit",
            "SETTINGS::SYSTEM:PTERODACTYL:TOKEN" => "pterodactyl-api-key",
            "SETTINGS::SYSTEM:ENABLE_LOGIN_LOGO" => "enable-login-logo",
            "SETTINGS::SYSTEM:PTERODACTYL:ADMIN_USER_TOKEN" => "pterodactyl-admin-api-key",
            "SETTINGS::SYSTEM:ENABLE_UPGRADE" => "enable-upgrade",
            "SETTINGS::SYSTEM:CREATION_OF_NEW_SERVERS" => "enable-disable-servers",
            "SETTINGS::SYSTEM:CREATION_OF_NEW_USERS" => "enable-disable-new-users",
            "SETTINGS::SYSTEM:SHOW_IMPRINT" => "show-imprint",
            "SETTINGS::SYSTEM:SHOW_PRIVACY" => "show-privacy",
            "SETTINGS::SYSTEM:SHOW_TOS" => "show-tos",
            "SETTINGS::SYSTEM:ALERT_ENABLED" => "alert-enabled",
            "SETTINGS::SYSTEM:ALERT_TYPE" => "alert-type",
            "SETTINGS::SYSTEM:ALERT_MESSAGE" => "alert-message",
            "SETTINGS::SYSTEM:THEME" => "theme",
            "SETTINGS::SYSTEM:MOTD_ENABLED" => "motd-enabled",
            "SETTINGS::SYSTEM:MOTD_MESSAGE" => "motd-message",
            "SETTINGS::SYSTEM:USEFULLINKS_ENABLED" => "usefullinks-enabled",
            "SETTINGS::SYSTEM:SEO_TITLE" => "seo-title",
            "SETTINGS::SYSTEM:SEO_DESCRIPTION" => "seo-description",
        ];

        foreach ($values as $key => $value) {
            $param = $request->get($value);

            Settings::where('key', $key)->updateOrCreate(['key' => $key], ['value' => $param]);
            Cache::forget('setting'.':'.$key);
        }

        //SET THEME
        $theme = $request->get('theme');
        Theme::set($theme);


        return redirect(route('admin.settings.index').'#system')->with('success', __('System settings updated!'));
    }

    private function updateIcons(Request $request)
    {
        $request->validate([
            'icon' => 'nullable|max:10000|mimes:jpg,png,jpeg',
            'logo' => 'nullable|max:10000|mimes:jpg,png,jpeg',
            'favicon' => 'nullable|max:10000|mimes:ico',
        ]);

        if ($request->hasFile('icon')) {
            $request->file('icon')->storeAs('public', 'icon.png');
        }
        if ($request->hasFile('logo')) {
            $request->file('logo')->storeAs('public', 'logo.png');
        }
        if ($request->hasFile('favicon')) {
            $request->file('favicon')->storeAs('public', 'favicon.ico');
        }
    }
}
