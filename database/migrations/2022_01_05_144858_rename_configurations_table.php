<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('configurations', 'settings');

        DB::table('settings')->where('key', 'INITIAL_CREDITS')->update(['key' => 'SETTINGS::USER:INITIAL_CREDITS']);
        DB::table('settings')->where('key', 'INITIAL_SERVER_LIMIT')->update(['key' => 'SETTINGS::USER:INITIAL_SERVER_LIMIT']);
        DB::table('settings')->where('key', 'CREDITS_REWARD_AFTER_VERIFY_EMAIL')->update(['key' => 'SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL']);
        DB::table('settings')->where('key', 'SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL')->update(['key' => 'SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL']);
        DB::table('settings')->where('key', 'CREDITS_REWARD_AFTER_VERIFY_DISCORD')->update(['key' => 'SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_DISCORD']);
        DB::table('settings')->where('key', 'SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD')->update(['key' => 'SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD']);
        DB::table('settings')->where('key', 'MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER')->update(['key' => 'SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER']);
        DB::table('settings')->where('key', 'SERVER_LIMIT_AFTER_IRL_PURCHASE')->update(['key' => 'SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE']);
        DB::table('settings')->where('key', 'FORCE_EMAIL_VERIFICATION')->update(['key' => 'SETTINGS::USER:FORCE_EMAIL_VERIFICATION']);
        DB::table('settings')->where('key', 'FORCE_DISCORD_VERIFICATION')->update(['key' => 'SETTINGS::USER:FORCE_DISCORD_VERIFICATION']);
        DB::table('settings')->where('key', 'REGISTER_IP_CHECK')->update(['key' => 'SETTINGS::SYSTEM:REGISTER_IP_CHECK']);
        DB::table('settings')->where('key', 'CREDITS_DISPLAY_NAME')->update(['key' => 'SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME']);
        DB::table('settings')->where('key', 'ALLOCATION_LIMIT')->update(['key' => 'SETTINGS::SERVER:ALLOCATION_LIMIT']);
        DB::table('settings')->where('key', 'SERVER_CREATE_CHARGE_FIRST_HOUR')->update(['key' => 'SETTINGS::SYSTEM:SERVER_CREATE_CHARGE_FIRST_HOUR']);
        DB::table('settings')->where('key', 'SALES_TAX')->update(['key' => 'SETTINGS::PAYMENTS:SALES_TAX']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('settings', 'configurations');

        DB::table('configurations')->where('key', 'SETTINGS::USER:INITIAL_CREDITS')->update(['key' => 'INITIAL_CREDITS']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:INITIAL_SERVER_LIMIT')->update(['key' => 'INITIAL_SERVER_LIMIT']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL')->update(['key' => 'CREDITS_REWARD_AFTER_VERIFY_EMAIL']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL')->update(['key' => 'SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_DISCORD')->update(['key' => 'CREDITS_REWARD_AFTER_VERIFY_DISCORD']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD')->update(['key' => 'SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER')->update(['key' => 'MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')->update(['key' => 'SERVER_LIMIT_AFTER_IRL_PURCHASE']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:FORCE_EMAIL_VERIFICATION')->update(['key' => 'FORCE_EMAIL_VERIFICATION']);
        DB::table('configurations')->where('key', 'SETTINGS::USER:FORCE_DISCORD_VERIFICATION')->update(['key' => 'FORCE_DISCORD_VERIFICATION']);
        DB::table('configurations')->where('key', 'SETTINGS::SYSTEM:REGISTER_IP_CHECK')->update(['key' => 'REGISTER_IP_CHECK']);
        DB::table('configurations')->where('key', 'SETTINGS::SYSTEM:SERVER_CREATE_CHARGE_FIRST_HOUR')->update(['key' => 'SERVER_CREATE_CHARGE_FIRST_HOUR']);
        DB::table('configurations')->where('key', 'SETTINGS::SERVER:ALLOCATION_LIMIT')->update(['key' => 'ALLOCATION_LIMIT']);
        DB::table('configurations')->where('key', 'SETTINGS::SERVER:CREDITS_DISPLAY_NAME')->update(['key' => 'SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME']);
        DB::table('configurations')->where('key', 'SETTINGS::PAYMENTS:SALES_TAX')->update(['key' => 'SALES_TAX']);
    }
};
