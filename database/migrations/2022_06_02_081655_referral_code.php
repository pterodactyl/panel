<?php

use App\Models\User;
use App\Traits\Referral;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    use Referral;
    public function setReferralCode($userid)
    {
        $code = $this->createReferralCode();
        DB::table('users')
            ->where('id', '=', $userid)
            ->update(['referral_code' => $code]);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->lenght(8)->nullable();
        });

        $existing_user = User::where('referral_code', '')->orWhere('referral_code', null)->get();

        foreach ($existing_user as $user) {
            $this->setReferralCode($user->id);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
};
