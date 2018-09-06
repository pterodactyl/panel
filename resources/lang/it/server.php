<?php

return [
    'ajax' => [
        'socket_error' => 'Impossibile collegarsi al server Socket.IO, ci potrebbe essere qualche problema di connessione. Il panello non funzionerà correttamente.',
        'socket_status' => 'Lo stato del server è cambiato a',
        'socket_status_crashed' => 'Questo server è stato identificato come CRASHATO.',
    ],
    'config' => [
        'allocation' => [
            'available' => 'Allocazioni Disponibili',
            'header' => 'Allocazioni Server',
            'header_sub' => "Controlla l'IP e le porte disponibili di questo server.",
            'help' => 'Aiuto Allocazione',
            'help_text' => "L'elenco a sinistra include tutti gli IP disponibili e le porte che sono aperte per il tuo server da usare per le connessioni in arrivo.",
        ],
        'database' => [
            'add_db' => 'Aggiungi un nuovo database.',
            'header' => 'Database',
            'header_sub' => 'Tutti i database disponibili per questo server.',
            'host' => 'Host MySQL',
            'no_dbs' => 'Non ci sono database disponibili per questo server.',
            'reset_password' => 'Ripristina Password',
            'your_dbs' => 'I tuoi Database',
        ],
        'sftp' => [
            'change_pass' => 'Cambia la Password SFTP',
            'conn_addr' => 'Indirizzo Connessione',
            'details' => 'Dettagli SFTP',
            'header' => 'Configurazione SFTP',
            'header_sub' => 'Dettagli account per le connessioni SFTP.',
            'warning' => "Assicurati che il client è settato per collegarti via SFTP e non il FTP o FTPS, c'è una differenza tra i protocolli.",
        ],
        'startup' => [
            'command' => 'Commando di Avvio',
            'edited' => 'Le variabili di avvio sono state modificate con successo.  Saranno applicate al prossimo avvio del server.',
            'edit_params' => 'Modifica Parametri',
            'header' => 'Avvia Configurazione',
            'header_sub' => 'Controllo gli argomenti del avvio server.',
            'startup_regex' => 'Verifica Regex',
            'startup_var' => 'Commando di avvio variabile',
            'update' => 'Aggiorna Parametri di Avvio',
        ],
    ],
    'files' => [
        'add' => [
            'create' => 'Crea File',
            'header' => 'Nuovo File',
            'header_sub' => 'Crea un nuovo file nel tuo server.',
            'name' => 'Nome File',
        ],
        'add_folder' => 'Crea Nuova Cartella',
        'add_new' => 'Aggiungi un nuovo File',
        'back' => 'Torna nella Gestione File',
        'delete' => 'Elimina',
        'edit' => [
            'header' => 'Modifica File',
            'header_sub' => 'Modifica il file dal browser.',
            'return' => 'Ritorna alla Gestione File',
            'save' => 'Salva File',
        ],
        'exceptions' => [
            'invalid_mime' => "Questo tipo di file non può essere editato con l'editore del Panello.",
            'list_directory' => "Un errore è accaduto durante l'analisi del contenuto di questa cartella. Riprova.",
            'max_size' => "Questo file è troppo grande per essere modificato con l'editore del Panello.",
        ],
        'file_name' => 'Nome File',
        'header' => 'Gestione File',
        'header_sub' => 'Controlla tutti i tuoi file direttamente dal browser.',
        'last_modified' => 'Ultima Modifica',
        'loading' => 'Carico la struttura iniziale dei file, può richiedere qualche secondo.',
        'mass_actions' => 'Azioni di massa',
        'seconds_ago' => 'secondi fa',
        'size' => 'Dimensione',
        'yaml_notice' => 'Stai modificando un file YAML. Questi files non accettano i tabs, devono usare i spazi. Abbiamo fatto in modo che premendo tab inserirà :dropdown spazi.',
    ],
    'index' => [
        'add_new' => 'Aggiungi Server',
        'allocation' => 'Allocazione',
        'command' => 'Inserisci un Commando Console',
        'connection' => 'Connessione default',
        'control' => 'Controlla server',
        'cpu_use' => 'Uso CPU',
        'disk_space' => 'Spazio Disco',
        'header' => 'Console Server',
        'header_sub' => 'Controlla il tuo server in tempo reale.',
        'memory_use' => 'Uso della Memoria',
        'mem_limit' => 'Limite Memoria',
        'server_info' => 'Informazioni Server',
        'title' => 'Server :name',
        'usage' => 'Uso',
        'xaxis' => 'Tempo (2 secondi di incremento)',
    ],
    'schedule' => [
        'actions' => [
            'command' => 'Invia Commando',
            'power' => 'Cambia Accensione',
        ],
        'current' => 'Programmi Correnti',
        'day_of_month' => 'Giorno del Mese',
        'day_of_week' => 'Giorno della Settimana',
        'header' => 'Programmazioni',
        'hour' => 'Ore del Giorno',
        'manage' => [
            'delete' => 'Elimina Programmazione',
            'header' => 'Gestione Programmazioni',
            'submit' => 'Aggiorna Programmazioni
',
        ],
        'minute' => 'Minuti del Ora',
        'new' => [
            'header' => 'Nuovo Programma',
        ],
        'task' => [
            'action' => 'Esegui Azione',
            'add_more' => 'Esegui un altro Programma',
            'payload' => 'Con Carico',
            'time' => 'Dopo',
        ],
        'toggle' => 'Cambia Stato',
    ],
    'tasks' => [
        'actions' => [
            'command' => 'Invia Comando',
        ],
        'header' => 'Operazioni Programmate',
        'new' => [
            'chain_do' => 'Fai',
            'custom' => 'Valore unico',
            'fri' => 'Venerdì',
            'header_sub' => 'Crea una nuova operazione pianificata in questo server.',
            'hour' => 'Ora',
            'type' => 'Tipo di pianificazione',
            'wed' => "Mercoledi'",
        ],
        'new_task' => 'Aggiungi nuova operazione',
    ],
    'users' => [
        'add' => 'Aggiungi nuovo sub-utente',
        'configure' => 'Configura permessi',
        'edit' => [
            'header' => 'Modifica sub-utente',
        ],
        'new' => [
            'copy_files' => [
                'title' => 'Copia Files',
            ],
            'db_header' => 'Controlla Database',
            'decompress_files' => [
                'description' => "Permetti all'utente di decomprimere archivi .zip e .tar(.gz).",
            ],
            'delete_files' => [
                'title' => 'Elimina File',
            ],
            'delete_schedule' => [
                'title' => 'Elimina Programmazione',
            ],
            'delete_subuser' => [
                'title' => 'Elimina Sottoutente',
            ],
            'delete_task' => [
                'description' => 'Permette un utente di eliminare un programmazione.',
                'title' => 'Elimina Programmazione',
            ],
            'download_files' => [
                'title' => 'Scarica Files',
            ],
            'edit_subuser' => [
                'description' => "Permetti ad un utente di modifcare i permessi assegnati ad un'altro sub-utente.",
            ],
            'email_help' => "Immetti l'email dell'utente che desideri di invitare a controllare questo server.",
            'header_sub' => 'Aggiungi un nuovo utente con dei permessi su questo server.',
            'kill' => [
                'title' => 'Termina Server',
            ],
            'list_files' => [
                'description' => "Permetti all'utente di vedere tutti i files e le cartelle in questo server ma non poterne vedere il contenuto.",
            ],
            'restart' => [
                'title' => 'Restarta Server',
            ],
            'stop' => [
                'title' => 'Termina server',
            ],
            'subuser_header' => 'Controlla i sub-utenti',
            'task_header' => 'Controlla le operazioni pianificate',
            'view_allocations' => [
                'description' => "Permette l'utente di vedere tutti gli IP e le porte assegnate a quel server.",
                'title' => 'Mostra Sedi',
            ],
            'view_subuser' => [
                'description' => 'Permetti agli utenti di vedere i permessi assegnati ai sub-utenti.',
            ],
        ],
    ],
];
