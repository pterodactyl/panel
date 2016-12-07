<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
 * Translated by Jakob Schrettenbrunner <dev@schrej.net>.
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
        'socket_error' => 'Wir konnten uns nicht zum Socket.IO server verbinden. Möglicherweise bestehen Netzwerkprobleme. Das Panel funktioniert unter Umständen nicht wie erwartet.',
        'socket_status' => 'Der status dieses Servers hat sich geändert zu:',
        'socket_status_crashed' => 'Dieser Server wurde als ABGESTÜRZT erkannt.',
    ],
    'index' => [
        'add_new' => 'Neuen Server hinzufügen',
        'memory_use' => 'Speicherverbrauch',
        'cpu_use' => 'CPU Verbrauch',
        'xaxis' => 'Zeit (2s Abstand)',
        'server_info' => 'Server Information',
        'connection' => 'Standardverbindung',
        'mem_limit' => 'Speichergrenze',
        'disk_space' => 'Festplattenspeicher',
        'control' => 'Systemsteuerung',
        'usage' => 'Verbrauch',
        'allocation' => 'Zuweisung',
        'command' => 'Konsolenbefehl eingeben',
    ],
    'files' => [
            'loading' => 'Lade Dateibaum, das könnte ein paar Sekunden dauern...',
            'yaml_notice' => 'Du bearbeitest gearde eine YAML Datei. Diese Dateien benötigen Leerzeichen. Wir haben dafür gesorgt dass Tabs automatisch durch :dropdown Leerzeichen ersetzt werden.',
            'back' => 'Zurück zum Dateimanager',
            'saved' => 'Datei erfolgreich gespeichert.',
    ],

];
