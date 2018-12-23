<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alert Levels
    |--------------------------------------------------------------------------
    |
    | The default sort of alert levels which can be called as functions on the
    | AlertsMessageBag class. This gives a convenient way to add certain type's
    | of messages.
    |
    | For example:
    |
    |     Alerts::info($message);
    |
    */

    'levels' => [
        'info',
        'warning',
        'danger',
        'success',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    |
    | The session key which is used to store flashed messages into the current
    | session. This can be changed if it conflicts with another key.
    |
    */

    'session_key' => 'alert_messages',
];
