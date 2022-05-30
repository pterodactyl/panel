<?php

return [
    /*
     * The number of days that must elapse before old activity log entries are deleted
     * from the database.
     */
    'prune_days' => env('APP_ACTIVITY_PRUNE_DAYS', 90),
];
