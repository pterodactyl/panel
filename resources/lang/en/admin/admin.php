<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'header' => [
        'title' => 'Administration',
        'overview' => 'Administrative Overview<small>A quick glance at your system.</small>',
        'admin' => 'Admin',
        'index' => 'Index',
    ],

    'content' => [
        'title' => 'System Information',
        'isLatest' => 'You are running Pterodactyl Panel version <code>{{ config('app.version') }}</code>. Your panel is up-to-date!',
        'notLatest' => 'Your panel is <strong>not up-to-date!</strong> The latest version is <a href="https://github.com/Pterodactyl/Panel/releases/v{{ $version->getPanel() }}" target="_blank"><code>{{ $version->getPanel() }}</code></a> and you are currently running version <code>{{ config('app.version') }}</code>.',
    ],

    'button' => [
        'discord' => 'Get Help <small>(via Discord)</small>';
        'doc' => 'Documentation',
        'github' => 'GitHub',
        'support' => 'Support the Project',
    ],
];