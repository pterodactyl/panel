<?php

return [
    'account' => [
        'current_password' => 'Parolă curentă',
        'delete_user' => 'Șterge Utilizator',
        'email_password' => 'Parola Email',
        'exception' => 'A aparut o eroare in incercarea de a-ti actualiza contul.',
        'first_name' => 'Prenume',
        'header' => 'Management Cont',
        'header_sub' => 'Modifică detaliile contului tău.',
        'invalid_pass' => 'Parola introdusă nu este validă pentru acest cont.',
        'last_name' => 'Nume',
        'new_email' => 'Adresă Email Nouă',
        'new_password' => 'Parolă Nouă',
        'new_password_again' => 'Repetă Noua Parolă',
        'totp_apps' => 'Trebuie să ai o aplicație TOTP(ex. Authenticator,DUO Mobile,Authy,Enpass) pentru a folosi această opțiune.',
        'totp_checkpoint_help' => 'Te rog verifică setările tale TOTP prin scanarea Codului QR din dreapta folosind aplicatia de autentificare din telefonul tău și scrie codul de 6 cifre generat de aplicație în spațiul de mai jos. Apasă tasta enter când ai terminat.',
        'totp_disable' => 'Dezactivează Autentificarea 2-Factori.',
        'totp_disable_help' => 'Pentru a dezactiva TOTP pe acest cont trebuie să introduci token-ul TOTP valid. O dată validat, protecția TOTP din acest cont va fi dezactivată.',
        'totp_enable' => 'Activează Autentificarea 2-Factori.',
        'totp_enabled' => 'Contul tău a fost activat cu verificarea TOTP. Te rog apasă butonul de close din spațiul ăsta pentru a termina.',
        'totp_enabled_error' => 'Token-ul TOTP intodus nu a putut fi verificat. Te rog incearcă iar.',
        'totp_enable_help' => 'Aparent nu ai Autentificarea 2-Factori activată. Această metodă de autentificare adaugă o barieră adițională ce previne accesul neautorizat în contul tău. Dacă o activezi, o să fie necesar să scrii un cod generat pe telefonul tău sau alt device ce suportă TOTP înainte de a termina login-ul.',
        'totp_header' => 'Autentificarea Doi-Factori',
        'totp_qr' => 'Cod QR TOTP',
        'totp_token' => 'Token TOTP',
        'update_email' => 'Actualizează Adresa Email',
        'update_identitity' => 'Actualizează Identitatea',
        'update_pass' => 'Actualizează Parola',
        'update_user' => 'Actualizează Utilizator',
        'username_help' => 'Numele tău de utilizator trebuie să fie unic contului tau, el poate conține următoarele caractere:
:requirements.',
    ],
    'api' => [
        'index' => [
            'create_new' => 'Creează o nouă cheie API.',
            'header' => 'Acces API',
            'header_sub' => 'Modifică cheile de acces API.',
            'list' => 'Chei API',
        ],
        'new' => [
            'allowed_ips' => [
                'description' => 'Scrie o listă delimitată de linii cu IP-urile care au acces la API folosind această cheie. Notația CIDR nu e permisă. Lasă gol pentru a permite orice adresă IP.',
                'title' => 'IP-uri Permise',
            ],
            'base' => [
                'information' => [
                    'description' => 'Întoarce o listă cu toate serverele la care acest cont are acces.',
                    'title' => 'Informații de Bază',
                ],
                'title' => 'Informații Bază',
            ],
            'descriptive_memo' => [
                'description' => 'Scrie o descriere scurtă despre ce o să faci cu acest API Key.',
                'title' => 'Memo Descriptiv',
            ],
            'form_title' => 'Detalii',
            'header' => 'Cheie API Nouă',
            'header_sub' => 'Creează o nouă cheie de acces API',
            'location_management' => [
                'list' => [
                    'description' => 'Permite listarea tuturor locațiilor și a node-urilor asociate.',
                    'title' => 'Lista Locațiilor',
                ],
                'title' => 'Management Locații',
            ],
            'node_management' => [
                'allocations' => [
                    'description' => 'Permite vederea tuturor alocațiilor în panou și a node-urilor.',
                    'title' => 'Listă Alocații',
                ],
                'create' => [
                    'description' => 'Permite creerea unui node nou în sistem.',
                    'title' => 'Creează Node',
                ],
                'delete' => [
                    'description' => 'Permite ștergerea unui node.',
                    'title' => 'Șterge Node',
                ],
                'list' => [
                    'description' => 'Permite listarea tuturor node-urilor prezente în sistem.',
                    'title' => 'Listă Node-uri',
                ],
                'title' => 'Management Node',
                'view' => [
                    'description' => 'Permite vederea detalilor unui node, inclusiv serviciile active.',
                    'title' => 'Listează Node-urile Singure',
                ],
            ],
            'server_management' => [
                'build' => [
                    'description' => 'Permite modificarea parametriilor unui server precum memoria, CPU, spațiul disk si IP-urile alocate sau default.',
                    'title' => 'Actualizează Build',
                ],
                'command' => [
                    'description' => 'Permite utilizatorului să trimită comenzi serverului specificat.',
                    'title' => 'Trimite Comandă',
                ],
                'config' => [
                    'description' => 'Permite modificarea configuraratiei server-ului(nume, detinator si token acces)',
                    'title' => 'Actualizează Configurația',
                ],
                'create' => [
                    'description' => 'Permite creerea unui nou server în sistem.',
                    'title' => 'Creează Server',
                ],
                'delete' => [
                    'description' => 'Permite ștergerea unui server.',
                    'title' => 'Șterge Server',
                ],
                'list' => [
                    'description' => 'Permite listarea tuturor serverelor din sistem.',
                    'title' => 'Listează Serverele',
                ],
                'power' => [
                    'description' => 'Permite accesul la starea server-ului.',
                    'title' => 'Stare Server',
                ],
                'server' => [
                    'description' => 'Permite accesul la informațiile despre un singur server, inclusiv statusul curent si alocarea.',
                    'title' => 'Info Server',
                ],
                'suspend' => [
                    'description' => 'Permite suspendarea unui server.',
                    'title' => 'Suspendă Server',
                ],
                'title' => 'Management Server',
                'unsuspend' => [
                    'description' => 'Permite reluarea unui server.',
                    'title' => 'Reluare Server',
                ],
                'view' => [
                    'description' => 'Permite accesul la detaliile unui server, inclusiv daemon_token si informații despre procesul curent.',
                    'title' => 'Arată un Singur Server',
                ],
            ],
            'service_management' => [
                'list' => [
                    'description' => 'Permite listarea tuturor serviciilor configurate în sistem.',
                    'title' => 'Listă Servicii',
                ],
                'title' => 'Management Servicii',
                'view' => [
                    'description' => 'Permite listarea detalilor despre fiecare serviciu în sistem, include opțiuniile serviciului si variabilele.',
                    'title' => 'Listează un Singur Serviciu',
                ],
            ],
            'user_management' => [
                'create' => [
                    'description' => 'Permite creearea unui nou user în sistem.',
                    'title' => 'Creează Utilizator',
                ],
                'delete' => [
                    'description' => 'Permite ștergerea unui utilizator.',
                    'title' => 'Șterge Utilizator',
                ],
                'list' => [
                    'description' => 'Permite listarea tuturor utilizator prezenți în sistem.',
                    'title' => 'Listează Utilizatorii',
                ],
                'title' => 'Management Utilizatori',
                'update' => [
                    'description' => 'Permite modificarea detalii utilizatori(email, parolă, informații TOTP).',
                    'title' => 'Actualizează Utilizator',
                ],
                'view' => [
                    'description' => 'Permite vederea detaliilor unui specific utilizator, inclusiv serviciilor active.',
                    'title' => 'Listează Utilizator Singur',
                ],
            ],
        ],
        'permissions' => [
            'admin' => [
                'location' => [
                    'list' => [
                        'desc' => 'Permite vizualizarea tuturor locațiilor și nodurilor.',
                        'title' => 'Vizualizează Locațiile',
                    ],
                ],
                'location_header' => 'Controlează Locația',
                'node' => [
                    'create' => [
                        'desc' => 'Permite crearea unui nou nod în sistem.',
                        'title' => 'Crează Nod',
                    ],
                    'delete' => [
                        'desc' => 'Permite ștergerea unui nod din sistem.',
                        'title' => 'Șterge Nod',
                    ],
                    'list' => [
                        'desc' => 'Permite vizualizarea tuturor nodurilor din sistem.',
                        'title' => 'Vezi Nodurile',
                    ],
                    'view-config' => [
                        'desc' => 'Pericol! Asta permite vizualizarea configurației nodului folosită de daemon și expune vizualizarea chei secrete a daemonului!',
                        'title' => 'Vezi Configurația Nodului',
                    ],
                    'view' => [
                        'desc' => 'Permite vizualizarea detaliilor despre un anumit nod incluzând și serverele lui active.',
                        'title' => 'Vezi Nodul',
                    ],
                ],
                'node_header' => 'Controlează Nodul',
                'pack' => [
                    'view' => [
                        'title' => 'Vezi Pachetul',
                    ],
                ],
                'server' => [
                    'edit-container' => [
                        'title' => 'Editează Containerul Serverului',
                    ],
                    'edit-details' => [
                        'desc' => 'Permite editarea detaliilor serverului, precum numele, proprietarul, descrierea sau cheia secretă.',
                        'title' => 'Editează Detaliile Serverului',
                    ],
                    'edit-startup' => [
                        'desc' => 'Permite modificarea comenzilor și parametrilor de start ai serverului.',
                        'title' => 'Editează Startup-ul Serverului',
                    ],
                    'install' => [
                        'title' => 'Comută Starea Instalării',
                    ],
                    'list' => [
                        'desc' => 'Permite vizualizarea tuturor serverelor din sistem.',
                        'title' => 'Listează Serverele',
                    ],
                    'rebuild' => [
                        'title' => 'Reconstruiește Serverul',
                    ],
                    'view' => [
                        'title' => 'Vezi Serverul',
                    ],
                ],
                'user' => [
                    'edit' => [
                        'desc' => 'Permite modificarea detaliilor utilizatorului',
                        'title' => 'Actualizează Utilizatorul',
                    ],
                ],
            ],
        ],
    ],
    'confirm' => 'Ești sigur?',
    'errors' => [
        '403' => [
            'desc' => 'Nu ai permisiunea să accesezi această resursă pe server.',
            'header' => 'Interzis',
        ],
        '404' => [
            'desc' => 'Nu am putut găsi această resursă pe server.',
            'header' => 'Fișierul nu a fost găsit',
        ],
        'home' => 'Mergi acasă',
        'return' => 'Întoarce-te la Pagina Precedentă',
    ],
    'form_error' => 'Următoarele erori au apărut în timpul încercării de a procesa această cerere.',
    'index' => [
        'header' => 'Consolă Server',
        'header_sub' => 'Controlează serverele în timp real.',
        'list' => 'Listă Servere',
    ],
    'no_servers' => 'Nu ai nici un server prezent în contul tău.',
    'password_req' => 'Parola trebuie să îndeplinească următoarele cerințe: cel puțin o literă mare, o literă mică, o cifră și să fie de minim 8 caractere în lungime.',
    'security' => [
        '2fa_checkpoint_help' => 'Folosește aplicația 2FA de pe telefonul tău și fă poză la codul QR din stânga sau introdu manual codul de sub el. După ce ai făcut asta, generează un token și scrie-l mai jos.',
        '2fa_disabled' => 'Autentificarea 2-Factori este dezactivată în contul tău! Ar trebui să activezi 2FA pentru a avea un nivel suplimentar de protecție în contul tău.',
        '2fa_enabled' => 'Autentificarea 2-Facotri este activată pe acest cont și este necesară pentru a te loga în panou. Dacă vrei să dezactivezi 2FA, scrie un token valid mai jos și trimite.',
        '2fa_header' => 'Autentificare 2-Factori',
        '2fa_qr' => 'Configurează 2FA în device-ul tău',
        '2fa_token_help' => 'Scrie Token-ul 2FA generat de aplicația ta (Google Authenticator,Authy,etc.).',
        'disable_2fa' => 'Dezactivează Autentificarea 2-Factori',
        'enable_2fa' => 'Activează Autentificarea 2-Factori',
        'header' => 'Securitate Cont',
        'header_sub' => 'Controlează sesiunile active și Autentificarea 2-Factori',
        'sessions' => 'Sesiuni Active',
    ],
    'server_name' => 'Nume Server',
    'validation_error' => 'A apărut o eroare cu unul sau mai multe câmpuri.',
    'view_as_admin' => 'Vezi acest server listat ca admin. Ca atare, toate serverele din sistem sunt afișate. Toate serverele la care tu ești deținător sunt afișate cu un punct albastru în fața numelui.',
];
