<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateOldAuditlogsToNewAuditlogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $logs = DB::table('audit_logs')
        ->where('action', '=', 'server:backup.started')
        ->orWhere('action', '=', 'server:backup.failed')
        ->orWhere('action', '=', 'server:backup.completed')
        ->orWhere('action', '=', 'server:backup.deleted')
        ->orWhere('action', '=', 'server:backup.downloaded')
        ->orWhere('action', '=', 'server:backup.locked')
        ->orWhere('action', '=', 'server:backup.unlocked')
        ->orWhere('action', '=', 'server:backup.restore.started')
        ->orWhere('action', '=', 'server:backup.restore.completed')
        ->orWhere('action', '=', 'server:backup.restore.failed')
        ->get();

        foreach ($logs as $record) {
            $record_metadata = json_decode($record->metadata);
            $backup = DB::table('backups')->where('uuid', '=', $record_metadata->backup_uuid)->first();
            $record_metadata = ['backup_uuid' => $backup->uuid, 'backup_name' => $backup->name];
            DB::table('audit_logs')->where('id', '=', $record->id)->update(['metadata' => $record_metadata]);

            if($record->action == "server:backup.started"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.start"]);
            }else if($record->action == "server:backup.failed"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.fail"]);
            }else if($record->action == "server:backup.completed"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.complete"]);
            }else if($record->action == "server:backup.deleted"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.delete"]);
            }else if($record->action == "server:backup.downloaded"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.download"]);
            }else if($record->action == "server:backup.locked"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.lock"]);
            }else if($record->action == "server:backup.unlocked"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.unlock"]);
            }else if($record->action == "server:backup.restore.started"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.restore.start"]);
            }else if($record->action == "server:backup.restore.completed"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.restore.complete"]);
            }else if($record->action == "server:backup.restore.failed"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.restore.fail"]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $logs = DB::table('audit_logs')
        ->where('action', '=', 'server:backup.start')
        ->orWhere('action', '=', 'server:backup.fail')
        ->orWhere('action', '=', 'server:backup.complete')
        ->orWhere('action', '=', 'server:backup.delete')
        ->orWhere('action', '=', 'server:backup.download')
        ->orWhere('action', '=', 'server:backup.lock')
        ->orWhere('action', '=', 'server:backup.unlock')
        ->orWhere('action', '=', 'server:backup.restore.start')
        ->orWhere('action', '=', 'server:backup.restore.complete')
        ->orWhere('action', '=', 'server:backup.restore.fail')
        ->get();

        foreach ($logs as $record) {
            $record_metadata = json_decode($record->metadata);
            $record_metadata = ['backup_uuid' => $record_metadata->backup_uuid];
            DB::table('audit_logs')->where('id', '=', $record->id)->update(['metadata' => $record_metadata]);

            if($record->action == "server:backup.start"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.started"]);
            }else if($record->action == "server:backup.fail"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.failed"]);
            }else if($record->action == "server:backup.complete"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.completed"]);
            }else if($record->action == "server:backup.delete"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.deleted"]);
            }else if($record->action == "server:backup.download"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.downloaded"]);
            }else if($record->action == "server:backup.lock"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.locked"]);
            }else if($record->action == "server:backup.unlock"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.unlocked"]);
            }else if($record->action == "server:backup.restore.start"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.restore.started"]);
            }else if($record->action == "server:backup.restore.complete"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.restore.completed"]);
            }else if($record->action == "server:backup.restore.fail"){
                DB::table('audit_logs')->where('id', '=', $record->id)->update(['action' => "server:backup.restore.failed"]);
            }
        }
    }
}