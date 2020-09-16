<?php

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ReplaceEggJsonWithYaml extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $eggs = DB::select('SELECT id, config_files, config_startup, config_logs FROM eggs;');
        foreach ($eggs as $egg) {
            $data = [
                'config_files' => $egg->config_files,
                'config_startup' => $egg->config_startup,
                'config_logs' => $egg->config_logs,
            ];

            foreach ($data as $key => $json) {
                $data[$key] = Yaml::dump(json_decode($json, true), 8, 2);
            }

            $data['id'] = $egg->id;

            DB::update(
                'UPDATE eggs SET config_files = :config_files, config_startup = :config_startup, config_logs = :config_logs WHERE id = :id;',
                $data
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $eggs = DB::select('SELECT id, config_files, config_startup, config_logs FROM eggs;');
        foreach ($eggs as $egg) {
            $data = [
                'config_files' => $egg->config_files,
                'config_startup' => $egg->config_startup,
                'config_logs' => $egg->config_logs,
            ];

            foreach ($data as $key => $yaml) {
                $data[$key] = json_encode(Yaml::parse($yaml), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            $data['id'] = $egg->id;

            DB::update(
                'UPDATE eggs SET config_files = :config_files, config_startup = :config_startup, config_logs = :config_logs WHERE id = :id;',
                $data
            );
        }
    }
}
