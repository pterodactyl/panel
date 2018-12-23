<?php

return [
    'ajax' => [
        'socket_error' => 'Me ei suutnud luua ühendust peamise Socket.IO serveriga, see võib tuleneda internetiprobleemidest või daemoni rikkest. Paneel ei pruugi töötada ootuspäraselt',
        'socket_status' => 'Selle serveri olek on muutunud',
        'socket_status_crashed' => 'See server on maas',
    ],
    'config' => [
        'allocation' => [
            'header_sub' => 'Kontrolli serveri IP-si ja Porte',
            'help' => 'Eraldamise abi',
        ],
        'database' => [
            'header' => 'Andmebaasid',
            'header_sub' => 'Kõik serverile saadaolevad andmebaasid',
            'host' => 'MySQL host ',
            'no_dbs' => 'Sellel serveril puuduvad andmebaasid',
            'reset_password' => 'Taasta salasõna',
            'your_dbs' => 'Teie andmebaasid',
        ],
        'sftp' => [
            'change_pass' => 'Vaheta SFTP salasõna',
            'conn_addr' => 'Ühenduse aadress',
            'details' => 'SFTP Detailid',
            'header' => 'STFP seadistus',
            'header_sub' => 'Kasutaja seaded SFTP ühenduse jaoks',
            'warning' => 'Palun veenduge, et Teie klient kasutab SFTP mitte FTP ega FTPS ühendusi, nende protokollid on erinevad',
        ],
        'startup' => [
            'command' => 'Käivitamise käsk',
            'edit_params' => 'Muuda parameetreid',
            'header' => 'Alusta seadistamist',
            'header_sub' => 'Kontrolli serveri käivitamisel kasutatavaid argumente',
            'update' => 'Uuenda käivitamise argumente',
        ],
    ],
    'files' => [
        'edit' => [
            'header' => 'Muuda faili',
            'header_sub' => 'Muuda faili läbi paneeli',
            'return' => 'Tagasi failihaldurisse',
            'save' => 'Salvesta fail',
        ],
        'file_name' => 'Faili nimi',
        'header' => 'Failihaldur',
        'header_sub' => 'Hallata kõiki oma faile otse veebis',
        'last_modified' => 'Viimati muudetud',
        'loading' => 'Laen algset faili struktuuri, see võib võtta mõne sekundi',
        'path' => 'Seadistades oma failiteed serveri pluginate või seadete jaoks peaksite kasutama :path oma baasiks. Faili maksimum suurus failihalduri kaudu lisades on :size',
        'saved' => 'Fail on edukalt salvestatud',
        'seconds_ago' => 'sekundit tagasi',
        'size' => 'Suurus',
        'yaml_notice' => 'Hetkel muudate YAML tüüpi faili. Sellised failid EI AKSEPTEERI tabi kasutust, selle asemel kasuta tühikut. Tabi kasutamisel sisestatakse :dropdown tühikut ',
    ],
    'index' => [
        'add_new' => 'Lisa uus server',
        'allocation' => 'Eraldamine',
        'command' => 'Sisesta konsooli kaudu käsklus',
        'connection' => 'Peamine ühendus',
        'control' => 'Serveri seaded',
        'cpu_use' => 'CPU kasutus',
        'header' => 'Serveri konsool',
        'header_sub' => 'Kontrolli oma serverit reaalajas',
        'memory_use' => 'Mälu kasutus ',
    ],
    'tasks' => [
        'actions' => [
            'command' => 'Saada käsk',
            'power' => 'Saada toite lülitus',
        ],
        'new' => [
            'payload_help' => 'Näiteks: Valides <code>Send Command</code> sisesta käsk siit. Valides <code>Send Power Option</code> sisesta süsteemi toite valik siia',
        ],
        'new_task' => 'Lisa uus käsk',
        'toggle' => 'Vaheta staatust',
    ],
    'users' => [
        'add' => 'Lisa uus alamkasutaja',
        'configure' => 'Seadista õiguseid',
        'edit' => [
            'header' => 'Muuda alamkasutajat',
            'header_sub' => 'Muuda kasutaja õiguseid serveris',
        ],
        'header' => 'Halda kasutajaid',
        'header_sub' => 'Kontrolli, kes pääseb teie serverisse',
        'list' => 'Õigustega kontod',
        'new' => [
            'command' => [
                'description' => 'Lubab käskluste saatmist läbi konsooli, kui kasutajal pole stop ja start õigusi, siis nad ei saa applikatsiooni peatada',
                'title' => 'Saada konsooli käsk',
            ],
            'compress_files' => [
                'description' => 'Lubab kasutajal arhiive failide ja kasutatdes süsteemis',
                'title' => 'Paki faile',
            ],
            'create_task' => [
                'description' => 'Lubab kasutajal luua uusi käske',
            ],
            'decompress_files' => [
                'description' => 'Lubab kasutajal lahtipakkuda .zip ja .tar(.gz) tüüpi faile',
            ],
            'download_files' => [
                'title' => 'Lae failid alla',
            ],
            'edit_subuser' => [
                'description' => 'Lubab kasutajal alamkasutaja õiguste muutmist',
                'title' => 'Muuda alamkasutajat',
            ],
            'email' => 'e-maili aadress',
            'email_help' => 'Sisestage e-maili aadress kasutaja jaoks, kellele soovite anda serveri jaoks õigusi',
            'file_header' => 'Faili haldamine',
            'header' => 'Lisa uus kasutaja',
            'header_sub' => 'Lisa uus kasutaja õigustega siia serverisse',
            'kill' => [
                'description' => 'Lubab kasutajal peatada serverit',
                'title' => 'Peata server',
            ],
            'list_files' => [
                'description' => 'Lubab kasutajal kuvada kõiki faile ja kaustu, kuid ei luba vaadata failide sisu',
                'title' => 'Kuva failid',
            ],
            'list_subusers' => [
                'description' => 'Lubab kasutajal vaadata kõiki alamkasutajaid serveris',
                'title' => 'Kuva alamkasutajad',
            ],
            'list_tasks' => [
                'description' => 'Luba kasutajal kuvada kõik ülesanded (sees ja väljas) serveris.',
                'title' => 'Kuva ülesanded',
            ],
            'move_files' => [
                'description' => 'Lubab kasutajal liigutada ja ümber nimetada faile ja kaustu',
                'title' => 'Nimeta ja liiguta faile',
            ],
            'power_header' => 'Toite haldamine',
            'queue_task' => [
                'description' => 'Lubab panna käsu järjekorda, mida jooksutada järgmine tsükkel',
                'title' => 'Pane ülesanne järjekorda',
            ],
            'reset_db_password' => [
                'description' => 'Lubab kasutajal taastada andmebaasi salasõnu',
                'title' => 'Taasta andmebaasi salasõna',
            ],
            'reset_sftp' => [
                'description' => 'Lubab kasutajal muuta serveri SFTP salasõna',
                'title' => 'Taasta SFTP salasõna',
            ],
            'save_files' => [
                'title' => 'Salvesta failid',
            ],
            'server_header' => 'Serveri haldus',
            'set_connection' => [
                'description' => 'Lubab kasutajal muuta peamist ühendustja porte, mida kasutatakse serveri jaoks',
                'title' => 'Vali vaikimisi ühendus',
            ],
            'sftp_header' => 'SFTP haldamine',
            'start' => [
                'description' => 'Lubab kasutajal alustada serverit',
                'title' => 'Käivita server',
            ],
            'stop' => [
                'description' => 'Lubab kasutajal peatada serveri',
                'title' => 'Peata server',
            ],
            'subuser_header' => 'Alamkasutaja haldus',
            'task_header' => 'Ülesannete haldamine',
            'toggle_task' => [
                'description' => 'Lubab kasutajal ülesande lülitada sisse või välja',
                'title' => 'Lülita task',
            ],
            'upload_files' => [
                'description' => 'Lubab kasutajal laadida faile ülesse läbi failihalduri',
                'title' => 'Lae faile',
            ],
            'view_databases' => [
                'description' => 'Lubab kasutajal vaadata kõiki andmebaase ja nendega seotuid kasutajanimesi ja paroole',
                'title' => 'Vaata andmebaasi detaile',
            ],
            'view_sftp' => [
                'description' => 'Lubab kasutajal vaadata serveri SFTP informatsiooni, kuid mitte parooli',
                'title' => 'Vaata SFTP detaile',
            ],
            'view_sftp_password' => [
                'description' => 'Lubab kasutajal vaadata SFTP parooli serveri jaoks',
                'title' => 'Vaata SFTP salasõna',
            ],
            'view_startup' => [
                'description' => 'Lubab kasutajal vaadata startup käske ja nendega seotud muutujaid',
            ],
        ],
    ],
];
