<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Addon Lifecycle Hooks
    |--------------------------------------------------------------------------
    |
    | When enabled, the Panel executes the hook scripts that addons place under
    | "addons/<name>/hooks/<event>" during lifecycle events such as post-install
    | (see the p:environment:addons:run-hooks command). These scripts run with
    | the privileges of the invoking process — often root during an upgrade — so
    | only enable this if you trust every installed addon.
    |
    */
    'hooks_enabled' => env('ADDONS_HOOKS_ENABLED', false),
];
