<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::statement('ALTER TABLE users MODIFY COLUMN language VARCHAR(5)');
                break;
            case 'pgsql':
                DB::statement('ALTER TABLE users ALTER COLUMN language TYPE varchar(5)');
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::statement('ALTER TABLE users MODIFY COLUMN language CHAR(5)');
                break;
            case 'pgsql':
                DB::statement('ALTER TABLE users ALTER COLUMN language TYPE CHAR(5)');
                break;
        }
    }
};
