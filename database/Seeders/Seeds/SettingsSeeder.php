<?php

namespace Database\Seeders\Seeds;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //initials
        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:INITIAL_CREDITS',
        ], [
            'value' => '250',
            'type' => 'integer',
            'description' => 'The initial amount of credits the user starts with.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:INITIAL_SERVER_LIMIT',
        ], [
            'value' => '1',
            'type' => 'integer',
            'description' => 'The initial server limit the user starts with.',
        ]);

        //verify email event
        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL',
        ], [
            'value' => '250',
            'type' => 'integer',
            'description' => 'Increase in credits after the user has verified their email account.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL',
        ], [
            'value' => '2',
            'type' => 'integer',
            'description' => 'Increase in server limit after the user has verified their email account.',
        ]);

        //verify discord event
        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_DISCORD',
        ], [
            'value' => '375',
            'type' => 'integer',
            'description' => 'Increase in credits after the user has verified their discord account.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD',
        ], [
            'value' => '2',
            'type' => 'integer',
            'description' => 'Increase in server limit after the user has verified their discord account.',
        ]);

        //other
        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER',
        ], [
            'value' => '50',
            'type' => 'integer',
            'description' => 'The minimum amount of credits the user would need to make a server.',
        ]);

        //purchasing
        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE',
        ], [
            'value' => '10',
            'type' => 'integer',
            'description' => 'updates the users server limit to this amount (unless the user already has a higher server limit) after making a purchase with real money, set to 0 to ignore this.',
        ]);

        //force email and discord verification
        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:FORCE_EMAIL_VERIFICATION',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Force an user to verify the email adress before creating a server / buying credits.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:FORCE_DISCORD_VERIFICATION',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Force an user to link an Discord Account before creating a server / buying credits.',
        ]);

        //disable ip check on register
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:REGISTER_IP_CHECK',
        ], [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'Prevent users from making multiple accounts using the same IP address',
        ]);

        //per_page on allocations request
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SERVER:ALLOCATION_LIMIT',
        ], [
            'value' => '200',
            'type' => 'integer',
            'description' => 'The maximum amount of allocations to pull per node for automatic deployment, if more allocations are being used than this limit is set to, no new servers can be created!',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER',
        ], [
            'value'       => '0',
            'type'        => 'integer',
            'description' => 'The minimum amount of credits user has to have to create a server. Can be overridden by package limits.'
        ]);

        //credits display name
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME',
        ], [
            'value' => 'Credits',
            'type' => 'string',
            'description' => 'The display name of your currency.',
        ]);

        //credits display name
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:SERVER_CREATE_CHARGE_FIRST_HOUR',
        ], [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'Charges the first hour worth of credits upon creating a server.',
        ]);
        //sales tax
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:SALES_TAX',
        ], [
            'value' => '0',
            'type' => 'integer',
            'description' => 'The %-value of tax that will be added to the product price on checkout.',
        ]);
        //Invoices enabled
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:ENABLED',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Enables or disables the invoice feature for payments.',
        ]);
        //Invoice company name
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:COMPANY_NAME',
        ], [
            'value' => '',
            'type' => 'string',
            'description' => 'The name of the Company on the Invoices.',
        ]);
        //Invoice company address
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:COMPANY_ADDRESS',
        ], [
            'value' => '',
            'type' => 'string',
            'description' => 'The address of the Company on the Invoices.',
        ]);
        //Invoice company phone
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:COMPANY_PHONE',
        ], [
            'value' => '',
            'type' => 'string',
            'description' => 'The phone number of the Company on the Invoices.',
        ]);

        //Invoice company mail
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:COMPANY_MAIL',
        ], [
            'value' => '',
            'type' => 'string',
            'description' => 'The email address of the Company on the Invoices.',
        ]);

        //Invoice VAT
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:COMPANY_VAT',
        ], [
            'value' => '',
            'type' => 'string',
            'description' => 'The VAT-Number of the Company on the Invoices.',
        ]);

        //Invoice Website
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:COMPANY_WEBSITE',
        ], [
            'value' => '',
            'type' => 'string',
            'description' => 'The Website of the Company on the Invoices.',
        ]);

        //Invoice Website
        Settings::firstOrCreate([
            'key' => 'SETTINGS::INVOICE:PREFIX',
        ], [
            'value' => 'INV',
            'type' => 'string',
            'description' => 'The invoice prefix.',
        ]);

        //Locale
        Settings::firstOrCreate([
            'key' => 'SETTINGS::LOCALE:DEFAULT',
        ], [
            'value' => 'en',
            'type' => 'string',
            'description' => 'The default dashboard language.',
        ]);
        //Dynamic locale
        Settings::firstOrCreate([
            'key' => 'SETTINGS::LOCALE:DYNAMIC',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'If this is true, the Language will change to the Clients browserlanguage or default.',
        ]);
        //User can change Locale
        Settings::firstOrCreate([
            'key' => 'SETTINGS::LOCALE:CLIENTS_CAN_CHANGE',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'If this is true, the clients will be able to change their Locale.',
        ]);
        //Locale
        Settings::firstOrCreate([
            'key' => 'SETTINGS::LOCALE:AVAILABLE',
        ], [
            'value' => 'en',
            'type' => 'string',
            'description' => 'The available languages.',
        ]);
        //Locale
        Settings::firstOrCreate([
            'key' => 'SETTINGS::LOCALE:DATATABLES',
        ], [
            'value' => 'en-gb',
            'type' => 'string',
            'description' => 'The Language of the Datatables. Grab the Language-Codes from here https://datatables.net/plug-ins/i18n/',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:PAYPAL:SECRET',
        ], [
            'value' => env('PAYPAL_SECRET', ''),
            'type' => 'string',
            'description' => 'Your PayPal Secret-Key (https://developer.paypal.com/docs/integration/direct/rest/).',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID',
        ], [
            'value' => env('PAYPAL_CLIENT_ID', ''),
            'type' => 'string',
            'description' => 'Your PayPal Client_ID.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET',
        ], [
            'value' => env('PAYPAL_SANDBOX_SECRET', ''),
            'type' => 'string',
            'description' => 'Your PayPal SANDBOX Secret-Key used for testing.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID',
        ], [
            'value' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
            'type' => 'string',
            'description' => 'Your PayPal SANDBOX Client-ID used for testing.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:STRIPE:SECRET',
        ], [
            'value' => env('STRIPE_SECRET', ''),
            'type' => 'string',
            'description' => 'Your Stripe Secret-Key (https://dashboard.stripe.com/account/apikeys).',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET',
        ], [
            'value' => env('STRIPE_ENDPOINT_SECRET', ''),
            'type' => 'string',
            'description' => 'Your Stripe endpoint secret-key.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:STRIPE:TEST_SECRET',
        ], [
            'value' => env('STRIPE_TEST_SECRET', ''),
            'type' => 'string',
            'description' => 'Your Stripe test secret-key.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:STRIPE:ENDPOINT_TEST_SECRET',
        ], [
            'value' => env('STRIPE_ENDPOINT_TEST_SECRET', ''),
            'type' => 'string',
            'description' => 'Your Stripe endpoint test secret-key.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::PAYMENTS:STRIPE:METHODS',
        ], [
            'value' => env('STRIPE_METHODS', 'card,sepa_debit'),
            'type' => 'string',
            'description' => 'Comma seperated list of payment methods that are enabled (https://stripe.com/docs/payments/payment-methods/integration-options).',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::DISCORD:CLIENT_ID',
        ], [
            'value' => env('DISCORD_CLIENT_ID', ''),
            'type' => 'string',
            'description' => 'Discord API Credentials (https://discordapp.com/developers/applications/).',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::DISCORD:CLIENT_SECRET',
        ], [
            'value' => env('DISCORD_CLIENT_SECRET', ''),
            'type' => 'string',
            'description' => 'Discord API Credentials (https://discordapp.com/developers/applications/).',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::DISCORD:BOT_TOKEN',
        ], [
            'value' => env('DISCORD_BOT_TOKEN', ''),
            'type' => 'string',
            'description' => 'Discord API Credentials (https://discordapp.com/developers/applications/).',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::DISCORD:GUILD_ID',
        ], [
            'value' => env('DISCORD_GUILD_ID', ''),
            'type' => 'string',
            'description' => 'Discord API Credentials (https://discordapp.com/developers/applications/).',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::DISCORD:ROLE_ID',
        ], [
            'value' => env('DISCORD_ROLE_ID', ''),
            'type' => 'string',
            'description' => 'Discord role that will be assigned to users when they register.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::DISCORD:INVITE_URL',
        ], [
            'value' => env('DISCORD_INVITE_URL', ''),
            'type' => 'string',
            'description' => 'The invite URL to your Discord Server.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:PTERODACTYL:TOKEN',
        ], [
            'value' => env('PTERODACTYL_TOKEN', ''),
            'type' => 'string',
            'description' => 'Admin API Token from Pterodactyl Panel - necessary for the Panel to work. The Key needs all read&write permissions!',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:PTERODACTYL:URL',
        ], [
            'value' => env('PTERODACTYL_URL', ''),
            'type' => 'string',
            'description' => 'The URL to your Pterodactyl Panel. Must not end with a / ',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:PTERODACTYL:PER_PAGE_LIMIT',
        ], [
            'value' => 200,
            'type' => 'integer',
            'description' => 'The Pterodactyl API perPage limit. It is necessary to set it higher than your server count.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MISC:PHPMYADMIN:URL',
        ], [
            'value' => env('PHPMYADMIN_URL', ''),
            'type' => 'string',
            'description' => 'The URL to your PHPMYADMIN Panel. Must not end with a /, remove to remove database button',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::RECAPTCHA:SITE_KEY',
        ], [
            'value' => env('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'),
            'type' => 'string',
            'description' => 'Google Recaptcha API Credentials (https://www.google.com/recaptcha/admin) - reCaptcha V2 (not v3)',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::RECAPTCHA:SECRET_KEY',
        ], [
            'value' => env('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'),
            'type' => 'string',
            'description' => 'Google Recaptcha API Credentials (https://www.google.com/recaptcha/admin) - reCaptcha V2 (not v3)',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::RECAPTCHA:ENABLED',
        ], [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'Enables or disables the ReCaptcha feature on the registration/login page.',

        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:MAILER',
        ], [
            'value' => env('MAIL_MAILER', 'smtp'),
            'type' => 'string',
            'description' => 'Selected Mailer (smtp, mailgun, sendgrid, mailtrap).',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:HOST',
        ], [
            'value' => env('MAIL_HOST', 'localhost'),
            'type' => 'string',
            'description' => 'Mailer Host Address.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:PORT',
        ], [
            'value' => env('MAIL_PORT', '25'),
            'type' => 'string',
            'description' => 'Mailer Server Port.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:USERNAME',
        ], [
            'value' => env('MAIL_USERNAME', ''),
            'type' => 'string',
            'description' => 'Mailer Username.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:PASSWORD',
        ], [
            'value' => env('MAIL_PASSWORD', ''),
            'type' => 'string',
            'description' => 'Mailer Password.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:ENCRYPTION',
        ], [
            'value' => env('MAIL_ENCRYPTION', 'tls'),
            'type' => 'string',
            'description' => 'Mailer Encryption (tls, ssl).',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:FROM_ADDRESS',
        ], [
            'value' => env('MAIL_FROM_ADDRESS', ''),
            'type' => 'string',
            'description' => 'Mailer From Address.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::MAIL:FROM_NAME',
        ], [
            'value' => env('APP_NAME', 'Controlpanel'),
            'type' => 'string',
            'description' => 'Mailer From Name.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::REFERRAL::ENABLED',
        ], [
            'value' => 'false',
            'type' => 'string',
            'description' => 'Enable or disable the referral system.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::REFERRAL::ALWAYS_GIVE_COMMISSION',
        ], [
            'value' => 'false',
            'type' => 'string',
            'description' => 'Whether referrals get percentage commission only on first purchase or on every purchase',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::REFERRAL::REWARD',
        ], [
            'value' => 100,
            'type' => 'integer',
            'description' => 'Credit reward a user should receive when a user registers with his referral code',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::REFERRAL::ALLOWED',
        ], [
            'value' => 'client',
            'type' => 'string',
            'description' => 'Who should be allowed to to use the referral code. all/client',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::REFERRAL:MODE',
        ], [
            'value' => 'sign-up',
            'type' => 'string',
            'description' => 'Whether referrals get Credits on User-Registration or if a User buys credits',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::REFERRAL:PERCENTAGE',
        ], [
            'value' => 100,
            'type' => 'integer',
            'description' => 'The Percentage value a referred user gets.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:PTERODACTYL:ADMIN_USER_TOKEN',
        ], [
            'value' => '',
            'type' => 'string',
            'description' => 'The Client API Key of an Pterodactyl Admin Account.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:ENABLE_UPGRADE',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Enables the updgrade/downgrade feature for servers.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:CREATION_OF_NEW_SERVERS',
        ], [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'Enable creation of new servers',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:CREATION_OF_NEW_USERS',
        ], [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'Enable creation of new users',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:SHOW_IMPRINT',
        ], [

            'value' => "false",
            'type'  => 'boolean',
            'description'  => 'Enable imprint in footer.'

        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:SHOW_PRIVACY',
        ], [

            'value' => "false",
            'type'  => 'boolean',
            'description'  => 'Enable privacy policy in footer.'

        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:SHOW_TOS',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Enable Terms of Service in footer.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:ALERT_ENABLED',
        ], [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Enable Alerts on Homepage.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:ALERT_TYPE',
        ], [
            'value' => 'dark',
            'type' => 'text',
            'description' => 'Changes the Color of the Alert.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:ALERT_MESSAGE',
        ], [
            'value' => '',
            'type' => 'text',
            'description' => 'Changes the Content the Alert.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:THEME',
        ], [
            'value' => 'default',
            'type' => 'text',
            'description' => 'Current active theme.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:USEFULLINKS_ENABLED',
        ], [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'Enable Useful Links on Homepage.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:MOTD_ENABLED',
        ], [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'Enable MOTD on Homepage.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:MOTD_MESSAGE',
        ], [
            'value' => '<h1 style="text-align: center;"><img style="display: block; margin-left: auto; margin-right: auto;" src="https://controlpanel.gg/img/controlpanel.png" alt="" width="200" height="200"><span style="font-size: 36pt;">Controlpanel.gg</span></h1>
 <p><span style="font-size: 18pt;">Thank you for using our Software</span></p>
 <p><span style="font-size: 18pt;">If you have any questions, make sure to join our <a href="https://discord.com/invite/4Y6HjD2uyU" target="_blank" rel="noopener">Discord</a></span></p>
 <p><span style="font-size: 10pt;">(you can change this message in the <a href="admin/settings#system">Settings</a> )</span></p>',
            'type' => 'text',
            'description' => 'MOTD Message.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:SEO_TITLE',
        ], [
            'value' => 'Controlpanel.gg',
            'type' => 'text',
            'description' => 'The SEO Title.',
        ]);

        Settings::firstOrCreate([
            'key' => 'SETTINGS::SYSTEM:SEO_DESCRIPTION',
        ], [
            'value' => 'Billing software for Pterodactyl Dashboard!',
            'type' => 'text',
            'description' => 'SEO Description.',
        ]);
        Settings::firstOrCreate([
            'key' => 'SETTINGS::TICKET:NOTIFY',
        ], [
            'value' => 'all',
            'type' => 'text',
            'description' => 'Who will get a Email Notifcation on new Tickets.',
        ]);
    }
}
