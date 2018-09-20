<?php

return [
    'ajax' => [
        'socket_error' => 'We konden niet verbinden met de hoofd Socket.IO server. Er kunnen momenteel netwerk problemen zijn. Het paneel zal niet werken zoals verwacht.',
        'socket_status' => 'De status van deze server is veranderd naar',
        'socket_status_crashed' => 'De server is gedetecteerd als: GECRASHT.',
    ],
    'config' => [
        'allocation' => [
            'available' => 'Beschikbare locaties',
            'header' => 'Server toewijzingen',
        ],
        'database' => [
            'header' => 'Databases',
            'your_dbs' => 'Jouw databases',
        ],
        'sftp' => [
            'change_pass' => 'Verander SFTP wachtwoord',
            'conn_addr' => 'Connectie adres',
            'header_sub' => 'Account details voor SFTP connecties',
        ],
        'startup' => [
            'command' => 'Opstart commando',
            'header_sub' => 'Beheer server opstart argumenten',
        ],
    ],
    'files' => [
        'add' => [
            'create' => 'Maak bestand',
            'header_sub' => 'Creëer een nieuw bestand op je server',
        ],
        'back' => 'Terug naar bestandsbeheer',
        'edit' => [
            'header' => 'Bewerk bestand',
            'return' => 'Keer terug naar de File Manager',
            'save' => 'Bewaar bestand',
        ],
        'last_modified' => 'Laatst gewijzigd',
        'seconds_ago' => 'seconden geleden',
    ],
    'index' => [
        'command' => 'Voeg console commando in',
        'cpu_use' => 'CPU verbruik',
        'memory_use' => 'Geheugen in gebruik',
        'mem_limit' => 'Geheugen limiet',
        'server_info' => 'Server informatie',
        'usage' => 'Gebruik',
    ],
    'tasks' => [
        'current' => 'Huidig Geplande Taken.',
        'header' => 'Geplande taken',
        'new' => [
            'day_of_month' => 'Dag van de maand',
            'day_of_week' => 'Dag van de week',
            'fri' => 'vrijdag',
            'header' => 'Nieuwe taak',
            'hour' => 'Uur',
            'submit' => 'Creëer taak ',
            'sun' => 'zondag',
            'tues' => 'Dinsdag',
            'wed' => 'Woensdag',
        ],
        'new_task' => 'Voeg nieuwe taak toe',
    ],
    'users' => [
        'add' => 'Voeg nieuwe subuser toe',
        'edit' => [
            'header' => 'Bewerk Subuser',
        ],
        'header_sub' => 'Beheer wie je server kan gebruiken',
        'list' => 'Accounts met toegang',
        'new' => [
            'command' => [
                'title' => 'Stuur Console Commando',
            ],
            'compress_files' => [
                'title' => 'Bestanden Comprimeren.',
            ],
            'copy_files' => [
                'title' => 'Kopieer bestanden',
            ],
            'create_files' => [
                'description' => 'Staat gebruikers toe om een nieuw bestand aan te maken binnen het paneel.',
            ],
            'create_task' => [
                'title' => 'Taak aanmaken',
            ],
            'db_header' => 'Database beheer',
            'delete_files' => [
                'description' => 'Sta gebruiker toe om bestanden van het systeem te verwijderen.',
            ],
            'delete_subuser' => [
                'description' => 'Sta gebruiker toe om andere sub gebruikers van de server te verwijderen.',
            ],
            'download_files' => [
                'title' => 'Download bestanden',
            ],
            'edit_files' => [
                'description' => 'Staat gebruikers toe om een bestand enkel te lezen.',
            ],
            'edit_subuser' => [
                'title' => 'Bewerk sub gebruiker',
            ],
            'email' => 'Email adres',
            'file_header' => 'Bestands beheer',
            'header' => 'Voeg nieuwe gebruiker toe',
            'kill' => [
                'title' => 'Dood server',
            ],
            'list_files' => [
                'title' => 'Toon bestanden',
            ],
            'list_tasks' => [
                'title' => 'Toon taken',
            ],
            'queue_task' => [
                'title' => 'Plan taak',
            ],
            'reset_db_password' => [
                'title' => 'Herstel database wachtwoord',
            ],
            'reset_sftp' => [
                'title' => 'Herstel SFTP wachtwoord',
            ],
            'restart' => [
                'title' => 'Herstart server',
            ],
            'server_header' => 'Server Beheer',
            'sftp_header' => 'SFTP beheer',
            'start' => [
                'description' => 'Staat gebruikers toe om de server te herstarten.',
            ],
            'stop' => [
                'description' => 'Sta gebruiker toe om de server te stoppen.',
            ],
            'toggle_task' => [
                'description' => 'Sta gebruiker toe om een taak aan/uit te zetten.',
            ],
            'upload_files' => [
                'description' => 'Staat gebruikers toe om bestanden te uploaden via het bestandsbeheer.',
            ],
            'view_databases' => [
                'title' => 'Geef database details weer',
            ],
            'view_schedule' => [
                'title' => 'Bekijk Schema',
            ],
            'view_sftp' => [
                'description' => 'Sta gebruiker toe om de SFTP informatie te zien, maar niet het wachtwoord.',
                'title' => 'Bekijk SFTP Details',
            ],
            'view_sftp_password' => [
                'description' => 'Staat gebruikers toe het SFTP wachtwoord te bekijken voor deze server.',
                'title' => 'Geef SFTP wachtwoord weer',
            ],
            'view_startup' => [
                'description' => 'Staat een gebruiker toe de startup command en de bijbehorende variabelen voor een server te bekijken.',
                'title' => 'Bekijk Startup Command',
            ],
            'view_subuser' => [
                'description' => 'Staat een gebruiker toe permissies van subusers te bekijken.',
                'title' => 'Toon sub gebruiker',
            ],
            'view_task' => [
                'description' => 'Staat een gebruiker toe details van een taak te bekijken.',
                'title' => 'Bekijk Taak',
            ],
        ],
        'update' => 'Werk sub gebruiker bij',
        'user_assigned' => 'De subuser is succesvol toegevoegd aan deze server.',
        'user_updated' => 'Rechten zijn bijgewerkt.',
    ],
];
