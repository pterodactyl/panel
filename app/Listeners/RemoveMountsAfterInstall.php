<?php

namespace Pterodactyl\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\MountServer;

class RemoveMountsAfterInstall
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $mounts = Mount::where('mount_on_install', '=', true)->get();
        foreach ($mounts as $mount) {
            MountServer::where('mount_id', $mount->id)->where('server_id', $event->server->id)->delete();
        }
    }
}
