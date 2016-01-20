<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Pterodactyl Language Strings for /server/{server} Routes
    |--------------------------------------------------------------------------
    */

    'ajax' => [
        'socket_error' => 'We were unable to connect to the main Socket.IO server, there may be network issues currently. The panel may not work as expected.',
        'socket_status' => 'This server\'s power status has changed to',
        'socket_status_crashed' => 'This server has been detected as CRASHED.',
    ],
    'index' => [
        'add_new' => 'Add New Server',
        'memory_use' => 'Memory Usage',
        'cpu_use' => 'CPU Usage',
        'xaxis' => 'Time (2s Increments)',
        'server_info' => 'Server Information',
        'connection' => 'Default Connection',
        'mem_limit' => 'Memory Limit',
        'disk_space' => 'Disk Space',
        'control' => 'Control Server',
        'usage' => 'Usage',
        'allocation' => 'Allocation',
        'command' => 'Enter Console Command',
    ],
    'files' => [
            'loading' => 'Loading file listing, this might take a few seconds...',
            'yaml_notice' => 'You are currently editing a YAML file. These files do not accept tabs, they must use spaces. We\'ve gone ahead and made it so that hitting tab will insert :dropdown spaces.',
            'back' => 'Back to File Manager',
            'saved' => 'File has successfully been saved.',
    ],

];
