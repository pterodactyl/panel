<?php

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Contracts\Encryption\Encrypter;

class StoreNodeTokensAsEncryptedValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws \Exception
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropUnique(['daemonSecret']);
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->char('uuid', 36)->after('id');
            $table->char('daemon_token_id', 16)->after('upload_size');

            $table->renameColumn('`daemonSecret`', 'daemon_token');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->text('daemon_token')->change();
        });

        /** @var \Illuminate\Contracts\Encryption\Encrypter $encrypter */
        $encrypter = Container::getInstance()->make(Encrypter::class);

        foreach (DB::select('SELECT id, daemon_token FROM nodes') as $datum) {
            DB::update('UPDATE nodes SET uuid = ?, daemon_token_id = ?, daemon_token = ? WHERE id = ?', [
                Uuid::uuid4()->toString(),
                substr($datum->daemon_token, 0, 16),
                $encrypter->encrypt(substr($datum->daemon_token, 16)),
                $datum->id,
            ]);
        }

        Schema::table('nodes', function (Blueprint $table) {
            $table->unique(['uuid']);
            $table->unique(['daemon_token_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            /** @var \Illuminate\Contracts\Encryption\Encrypter $encrypter */
            $encrypter = Container::getInstance()->make(Encrypter::class);

            foreach (DB::select('SELECT id, daemon_token_id, daemon_token FROM nodes') as $datum) {
                DB::update('UPDATE nodes SET daemon_token = ? WHERE id = ?', [
                    $datum->daemon_token_id . $encrypter->decrypt($datum->daemon_token),
                    $datum->id,
                ]);
            }
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropUnique(['daemon_token_id']);
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'daemon_token_id']);
            $table->renameColumn('daemon_token', 'daemonSecret');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->string('daemonSecret', 36)->change();
            $table->unique(['daemonSecret']);
        });
    }
}
