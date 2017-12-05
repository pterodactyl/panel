<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Console\Kernel;

trait DatabaseTruncations
{
    /**
     * Define hooks to truncate and reseed our database before and after each test.
     *
     * @return void
     */
    public function runDatabaseTruncations()
    {
        $this->artisan('migrate', [
            '--seed' => true,
        ]);
        $this->app[Kernel::class]->setArtisan(null);

        $this->beforeApplicationDestroyed(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $tableNames = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
            foreach ($tableNames as $name) {
                if ($name == 'migrations') {
                    continue;
                }
                DB::table($name)->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        });
    }
}
