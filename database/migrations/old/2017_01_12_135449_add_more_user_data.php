<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Models\User;

class AddMoreUserData extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name_first')->after('email')->nullable();
            $table->string('name_last')->after('name_first')->nullable();
            $table->string('username')->after('uuid');
            $table->boolean('gravatar')->after('totp_secret')->default(true);
        });

        DB::transaction(function (): void {
            foreach (User::all() as &$user) {
                $user->username = $user->email;
                $user->save();
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('name_first');
            $table->dropColumn('name_last');
            $table->dropColumn('username');
            $table->dropColumn('gravatar');
        });
    }
}
