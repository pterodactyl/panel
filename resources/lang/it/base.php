<?php

return [
    'account' => [
        'current_password' => 'Password Corrente',
        'delete_user' => 'Elimina Utente',
        'details_updated' => 'I tuoi dettagli del account sono stati aggiornati con successo.',
        'email_password' => 'Email Password',
        'exception' => "E' stato trovato un'errore procedendo la richiesta d'aggiornamento account.",
        'first_name' => 'Primo Nome',
        'header' => 'CONTROLLO ACCOUNT',
        'header_sub' => 'Controlla i dettagli del tuo account.',
        'invalid_pass' => 'Password invalida per questo account',
        'invalid_password' => 'La password inserita per il tuo account non è valida.',
        'last_name' => 'Cognome',
        'new_email' => 'Nuovo Indirizzo Email',
        'new_password' => 'Nuova password',
        'new_password_again' => 'Ripeti la Nuova Password',
        'totp_apps' => "Devi avere un'applicazione che supporti TOTP (Google Authenticator, DUO Mobile, Authy, Enpass) per usare questa opzione.",
        'totp_checkpoint_help' => 'Per favore verifica che le tue impostazioni TOTP facendo la scansione del Codice QR a destra usando un autenticatore sul tuo cellulare, e digita i 6 numeri generati dalla applicazione nella seguente casella. Premi invio quando hai finito.',
        'totp_disable' => 'Disabilita la Autenticazione a 2-Fattori',
        'totp_disable_help' => 'Per disabilitare la funzione TOTP in questo account dovrai dare un token TOTP valido. Quando verrà validato, la protezione TOTP verrà disabilitata in questo account',
        'totp_enable' => "Abilità l'autenticazione a due fattori",
        'totp_enabled' => 'Il tuo account è stato abilitato con la verificazione TOTP. Perfavore clicca il bottone chiudi per finire.',
        'totp_enabled_error' => 'Non è stato possibile verificare Il token TOTP inserito. Riprova.',
        'totp_enable_help' => "Sembra che tu non abbia l'autenticazione a due fattori abilitatà. Questo metodo di autenticazione aggiunge un'addizionale barriera di sicurezza prevenendo gli inautorizzati ad entrare nel tuo account. Se abiliti questa funzione ti sarà richiesto un codice generate dal tuo telefono o un'altra applicazione con supporto TOTP prima di finire il login",
        'totp_header' => 'Autenticazione a Due Fattori',
        'totp_qr' => 'Codice TOTP QR',
        'totp_token' => 'Token TOTP',
        'update_email' => 'Aggiorna Email',
        'update_identitity' => 'Aggiorna Identità',
        'update_pass' => 'Aggiorna Password',
        'update_user' => 'Aggiorna utente',
        'username_help' => 'II tuo username deve essere unico nel tuo account, e può contenere solo i seguenti caratteri: :requisements.',
    ],
    'api' => [
        'index' => [
            'create_new' => 'Crea una nuova chiave API',
            'header' => 'Accesso API',
            'header_sub' => "Gestisci le tue chiavi per l'accesso all'API",
            'keypair_created' => 'Una Chiave API è stata generata. Il tuo codice segreto API è  <code>:token</code>. Prendi nota di questo codice dato che non sarà più mostrato di nuovo.',
            'list' => 'Chiavi API',
        ],
        'new' => [
            'allowed_ips' => [
                'description' => "Immetti qualunque IP che ha il permesso di accedere all'API usando questa key. Le notazioni CIDR sono permesse. Lascia vuoto per permettere qualunque IP.",
                'title' => 'IP permessi',
            ],
            'base' => [
                'information' => [
                    'description' => 'Il suo output è una lista di server che questo account possiede.',
                    'title' => 'Informazioni Base',
                ],
                'title' => 'Informazioni Base',
            ],
            'descriptive_memo' => [
                'description' => 'Immetti una breve descrizione su cosa questa chiave API verrà usata.',
                'title' => 'Descrizione Memo',
            ],
            'form_title' => 'Dettagli',
            'header' => 'Nuova Chiave API',
            'header_sub' => 'Crea una nuova chiave di accesso API',
            'location_management' => [
                'list' => [
                    'description' => 'Permette di mostrare le locazioni e i loro nodi associati.',
                    'title' => 'Mostra Locazioni',
                ],
                'title' => 'Gestione Locazioni',
            ],
            'node_management' => [
                'allocations' => [
                    'description' => 'Permette di vedere tutte le sedi del panello per tutti i nodi.',
                    'title' => 'Mostra Sedi',
                ],
                'create' => [
                    'description' => 'Permette di creare un nuovo nodo nel sistema.',
                    'title' => 'Crea Nodo',
                ],
                'delete' => [
                    'description' => 'Permette di eliminare un nodo.',
                    'title' => 'Elimina nodo',
                ],
                'list' => [
                    'description' => 'Permette di mostrare tutti i nodi nel sistema.',
                    'title' => 'Elenco Nodi',
                ],
                'title' => 'Amministrazione nodi',
                'view' => [
                    'description' => 'Permette di vedere i dettagli di un nodo specifico inclusi  anche i servizi attivi.',
                    'title' => 'Elenca un Singolo Nodo',
                ],
            ],
            'server_management' => [
                'build' => [
                    'description' => "Permette l'utente di modificare i parametri del server come la memoria, CPU, e lo spazio del disco assegnati a esso e gli IP predefiniti.",
                    'title' => 'Aggiorna versione',
                ],
                'command' => [
                    'description' => 'Permette di inviare un commando in un server specifico',
                    'title' => 'Invia Commando',
                ],
                'config' => [
                    'description' => 'Permette di modificare la configurazione del server (nome, proprietario, e token di accesso).',
                    'title' => 'Aggiorna Configurazione',
                ],
                'create' => [
                    'description' => 'Permette di creare un nuovo server nel sistema.',
                    'title' => 'Crea Server',
                ],
                'delete' => [
                    'description' => 'Permette di eliminare un server.',
                    'title' => 'Elimina Server',
                ],
                'list' => [
                    'description' => 'Il suo output è una lista di tutti i server nel sistema.',
                    'title' => 'Elenco Server',
                ],
                'power' => [
                    'description' => 'Permette il controllo alle impostazioni di energia del server.',
                    'title' => 'Server Power',
                ],
                'server' => [
                    'description' => 'Permette di vedere le informazioni di un singolo server incluso lo stato e le allocazioni.',
                    'title' => 'Informazioni Server',
                ],
                'suspend' => [
                    'description' => "Permetti di sospendere un'instanza di un server.",
                    'title' => 'Sospendi Server',
                ],
                'title' => 'Gestione Server',
                'unsuspend' => [
                    'description' => "Permetti di sospendere un'instanza di un server.",
                    'title' => 'Riattiva Server',
                ],
                'view' => [
                    'description' => 'Permette di vedere i dettagli di un server specifico incluso il token e informazioni sui processi.',
                    'title' => 'Mostra server singolo',
                ],
            ],
            'service_management' => [
                'list' => [
                    'description' => 'Permetti di mostrare tutti i servizi configurati nel sistema.',
                    'title' => 'Elenco Servizi',
                ],
                'title' => 'Amministrazione servizi',
                'view' => [
                    'description' => 'Permette di elencare ogni servizio del sistema incluse le opzioni di servizio e le variabili.',
                    'title' => 'Elenco Servizio Singolo',
                ],
            ],
            'user_management' => [
                'create' => [
                    'description' => 'Permette di creare un nuovo utente in questo sistema.',
                    'title' => 'Crea Utente',
                ],
                'delete' => [
                    'description' => 'Permette di eliminare un utente.',
                    'title' => 'Elimina Utente',
                ],
                'list' => [
                    'description' => 'Permette di elencare tutti gli utenti presenti nel sistema.',
                    'title' => 'Mostra utenti',
                ],
                'title' => 'Gestione Utente',
                'update' => [
                    'description' => 'Permette di modificare delle credenziali utente (email, password, informazioni TOTP).',
                    'title' => 'Aggiorna Utente',
                ],
                'view' => [
                    'description' => "Permette di vedere dettagli riguardanti un'utente specifico includendo i servizi attivi.",
                    'title' => 'Elenco Utente Singolo',
                ],
            ],
        ],
        'permissions' => [
            'admin' => [
                'location' => [
                    'list' => [
                        'desc' => 'Permette di elencare tutte le locazioni e i loro nodi associati.',
                        'title' => 'Elenco Locazioni',
                    ],
                ],
                'location_header' => 'Controllo Locazione',
                'node' => [
                    'create' => [
                        'desc' => 'Permette di creare un nuovo nodo nel sistema.',
                        'title' => 'Crea Nodo',
                    ],
                    'delete' => [
                        'desc' => 'Permette di eliminare un nodo dal sistema.',
                        'title' => 'Elimina Nodo',
                    ],
                    'list' => [
                        'desc' => 'Permette di elencare tutti i nodi del sistema',
                        'title' => 'Elenco Nodi',
                    ],
                    'view-config' => [
                        'desc' => 'Attenzione. Questa opzione permette di vedere la configurazione del nodo usato dal demone, e mostra i codici segreti. ',
                        'title' => 'Mostra Configurazione Nodo',
                    ],
                    'view' => [
                        'desc' => 'Permette di mostrare i dettagli di un nodo specifico inclusi i servizi attivi.',
                        'title' => 'Mostra Nodo',
                    ],
                ],
                'node_header' => 'Controllo Nodo',
                'option' => [
                    'list' => [
                        'title' => 'Elenco Opzioni',
                    ],
                    'view' => [
                        'title' => 'Mostra Opzione',
                    ],
                ],
                'option_header' => 'Controllo Opzioni',
                'pack' => [
                    'list' => [
                        'title' => 'Elenco Pacchetti',
                    ],
                    'view' => [
                        'title' => 'Mostra Pacchetto',
                    ],
                ],
                'pack_header' => 'Gestione Pacchetti',
                'server' => [
                    'create' => [
                        'desc' => 'Permette la creazione di un nuovo server nel sistema.',
                        'title' => 'Crea Server',
                    ],
                    'delete' => [
                        'desc' => 'Permette di eliminare un server dal sistema.',
                        'title' => 'Elimina Server',
                    ],
                    'edit-build' => [
                        'desc' => 'Permette la modifica delle impostazioni del server come la CPU e memoria allocata. ',
                        'title' => 'Modifica Installazione Server',
                    ],
                    'edit-container' => [
                        'desc' => 'Permette di modificare il contenitore docker dove il server è in esecuzione.',
                        'title' => 'Modifica Container Server',
                    ],
                    'edit-details' => [
                        'desc' => 'Permette di modificare i dettagli server come nome, proprietario, descrizione, e chiave segreta.',
                        'title' => 'Modifica Dettagli Server',
                    ],
                    'edit-startup' => [
                        'desc' => 'Permette di modificare i comandi di avvio e i parametri del server.',
                        'title' => 'Modifica Avvio Server',
                    ],
                    'install' => [
                        'title' => 'Cambia Stato Installazione',
                    ],
                    'list' => [
                        'desc' => 'Permette di elencare lo stato di tutti i server presenti nel sistema.',
                        'title' => 'Elenco Server',
                    ],
                    'rebuild' => [
                        'title' => 'Ricostruisci Server',
                    ],
                    'suspend' => [
                        'desc' => 'Permette di sospendere e di riprendere un server dato.',
                        'title' => 'Sospendi Server',
                    ],
                    'view' => [
                        'desc' => 'Permette di vedere un singolo server incluso il servizio e dettagli.',
                        'title' => 'Mostra Server',
                    ],
                ],
                'server_header' => 'Controllo Server',
                'service' => [
                    'list' => [
                        'desc' => 'Permette di elencare tutti i servizi configurati nel sistema.',
                        'title' => 'Elenco Servizi',
                    ],
                    'view' => [
                        'desc' => 'Permette di elencare i dettagli di tutti i servizi del sistema incluse le opzioni e variabili.',
                        'title' => 'Mostra Servizio',
                    ],
                ],
                'service_header' => 'Controllo Servizio',
                'user' => [
                    'create' => [
                        'desc' => 'Permette la creazione di nuovi utenti nel sistema.',
                        'title' => 'Crea Utente',
                    ],
                    'delete' => [
                        'desc' => 'Permette di eliminare un utente.',
                        'title' => 'Elimina Utente',
                    ],
                    'edit' => [
                        'desc' => 'Permette di modificare i dettagli utente.',
                        'title' => 'Aggiorna Utente.',
                    ],
                    'list' => [
                        'desc' => 'Permette di vedere tutti gli utenti presenti nel sistema.',
                        'title' => 'Elenco Utenti',
                    ],
                    'view' => [
                        'desc' => 'Permette di vedere i dettagli di un specifico utente inclusi i servizi attivi.',
                        'title' => 'Vedi Utente',
                    ],
                ],
                'user_header' => 'Controllo Utente',
            ],
            'user' => [
                'server' => [
                    'command' => [
                        'desc' => 'Permette di eseguire i comandi di un server.',
                        'title' => 'Manda Commando',
                    ],
                    'list' => [
                        'desc' => 'Permette di elencare tutti i servizi che un utente usa o che ha accesso come sottoutente.',
                        'title' => 'Elenco Server',
                    ],
                    'power' => [
                        'desc' => 'Permette di cambiare lo stato di accensione di un server.',
                        'title' => 'Attiva/Disattiva',
                    ],
                    'view' => [
                        'desc' => "Permette di vedere i server specifici in cui l'utente può entrare.",
                        'title' => 'Vedi Server',
                    ],
                ],
                'server_header' => 'Permessi Server del utente',
            ],
        ],
    ],
    'confirm' => 'Sei sicuro?',
    'errors' => [
        '403' => [
            'desc' => 'Non hai il permesso di accedere a questa risorsa in questo server.',
            'header' => 'Proibito',
        ],
        '404' => [
            'desc' => 'La risorsa non è stata trovata nel nostro server.',
            'header' => 'File Non Trovato',
        ],
        'home' => 'Torna alla Home',
        'installing' => [
            'desc' => 'Il server richiesto sta ancora completando la fase di installazione. Riprova tra qualche minuto, dovresti ricvere una email quando sarà completato.',
            'header' => 'Installazione Server',
        ],
        'return' => 'Ritorna alla pagina precedente',
        'suspended' => [
            'desc' => 'Il server è stato sospeso e non è accessibile.',
            'header' => 'Server Sospeso',
        ],
    ],
    'index' => [
        'header' => 'Console Server',
        'header_sub' => 'Controlla il tuo server in tempo reale.',
        'list' => 'Lista server',
    ],
    'no_servers' => 'Non hai dei server nel tuo account',
    'password_req' => 'La password deve avere almeno un carattere in maiuscolo e uno in minuscolo, un numero/carattere speciale e deve essere lunga almeno 8 caratteri',
    'security' => [
        '2fa_disabled' => "L'autenticazione a due fattori è disabilitata nel tuo account! Dovresti abilitare l'autenticazione a due passi per aggiungere un'ulteriore strato di protezione al tuo account.",
        '2fa_qr' => "Configura l'autenticazione a 2 passi sul tuo dispositivo.",
        '2fa_token_help' => 'Entra il token 2FA generato dalla tua applicazione(Google Authenticator, Authy, etc.).',
        'header' => 'Sicurezza Account',
        'sessions' => 'Sessioni Attive',
    ],
    'server_name' => 'Nome Server',
    'view_as_admin' => 'Stai vedendo questo server come admin. Tutti i tipi di server nel sistema sono visibili. I server che possiedi sono marcati con un puntino blu vicino al loro nome.',
];
