<?php

return [
    'account' => [
        'current_password' => 'Aktuelles Passwort',
        'delete_user' => 'Benutzer löschen',
        'details_updated' => 'Dein Account wurde erfolgreich bearbeitet.',
        'email_password' => 'Email Passwort',
        'exception' => 'Während dem aktualisieren deines Kontos ist ein Fehler aufgetreten.',
        'first_name' => 'Vorname',
        'header' => 'BENUTZERVERWALTUNG',
        'header_sub' => 'Verwalte deine Kontodetails.',
        'invalid_pass' => 'Das angegebene Passwort ist für dieses Konto falsch.',
        'invalid_password' => 'Das Passwort war leider ungültig.',
        'last_name' => 'Nachname',
        'new_email' => 'Neue E-Mail Adresse',
        'new_password' => 'Neues Passwort',
        'new_password_again' => 'Neues Passwort wiederholen',
        'totp_disable' => 'Deaktiviere die Zwei-Faktor-Authentifizierung',
        'totp_enable' => 'Zwei-Faktor-Authentifizierung aktivieren',
        'totp_enable_help' => 'Es sieht so aus als hättest du die Zwei-Faktor-Authentifizierung deaktiviert. Diese Authentifizierungsmethode schützt dein Konto zusätzlich vor unerlaubtem Zugriff. Wenn du sie aktivierst musst du zukünftig neben deinem Passwort auch einen Code, der von deinem Smartphone oder einem anderen TOTP fähigen Gerät generiert wird, eingeben um dich anzumelden.',
        'totp_header' => 'Zwei-Faktor Authentifizierung',
        'update_email' => 'E-Mail Adresse aktualisieren',
        'update_identitity' => 'Kotodetails aktualisieren',
        'update_identity' => 'Konto bearbeiten',
        'update_pass' => 'Passwort ändern',
        'update_user' => 'Benutzer aktualisieren',
        'username_help' => 'Dein Benutzername muss für dein Konto einzigartig sein und darf nur die folgenden Zeichen enthalten: :requirements.',
    ],
    'api' => [
        'index' => [
            'create_new' => 'Neuen API Schlüssel erstellen',
            'header' => 'API Zugriff',
            'header_sub' => 'Verwalte deine API Zugangsschlüssel.',
            'keypair_created' => 'Ein API-Schlüsselpaar wurde generiert. Dein API Secret Token ist <code>: token </ code>. Bitte notiere diesen Schlüssel, da er nicht mehr angezeigt wird. ',
            'list' => 'API Schlüssel',
        ],
        'new' => [
            'allowed_ips' => [
                'description' => 'Gib zeilenweise alle IP Adressen an, die diesen Schlüssel verwenden können um auf die API zuzugreifen. CIDR-Notation ist erlaubt. Lass das Feld leer um beliebige IPs zu erlauben.',
                'title' => 'Erlaubte IP-Adressen',
            ],
            'base' => [
                'information' => [
                    'description' => 'Gibt eine Liste aller Server zurück auf die das Konto Zugriff hat.',
                    'title' => 'Basisinformationen',
                ],
                'title' => 'Basisinformationen',
            ],
            'descriptive_memo' => [
                'description' => 'Gebe dem API Schlüssel eine kurze Beschreibung.',
                'title' => 'Beschreibung',
            ],
            'form_title' => 'Details',
            'header' => 'Neuer API Schlüssel',
            'header_sub' => 'Erzeuge einen neuen API Schlüssel',
            'location_management' => [
                'list' => [
                    'title' => 'Liste Standorte',
                ],
                'title' => 'Orte verwalten',
            ],
            'node_management' => [
                'allocations' => [
                    'description' => 'Erlaubt es alle Zuweisungen (IP und Port) für alle Nodes im Panel aufzulisten.',
                    'title' => 'Zuweisungen auflisten',
                ],
                'create' => [
                    'description' => 'Erlaubt es neue Nodes zu erstellen.',
                    'title' => 'Node erstellen',
                ],
                'delete' => [
                    'description' => 'Erlaubt es eine Node zu löschen.',
                    'title' => 'Node löschen',
                ],
                'list' => [
                    'description' => 'Erlaubt die Auflistung aller Nodes.',
                    'title' => 'Nodes auflisten',
                ],
                'title' => 'Nodeverwaltung',
                'view' => [
                    'description' => 'Erlaubt es Details zu einer Node abzurufen.',
                    'title' => 'Einzelne Node anzeigen',
                ],
            ],
            'server_management' => [
                'command' => [
                    'title' => 'Befehle senden',
                ],
                'config' => [
                    'title' => 'Konfiguration aktualisieren',
                ],
                'create' => [
                    'description' => 'Erlaubt es neue Server zu erstellen.',
                    'title' => 'Server erstellen',
                ],
                'delete' => [
                    'description' => 'Ermöglicht es, Server zu löschen.',
                    'title' => 'Server löschen',
                ],
                'list' => [
                    'title' => 'Server auflisten',
                ],
                'server' => [
                    'title' => 'Server Informationen',
                ],
                'suspend' => [
                    'description' => 'Ermöglicht das Suspendieren einer Serverinstanz.',
                    'title' => 'Server suspendieren',
                ],
                'title' => 'Serververwaltung',
                'unsuspend' => [
                    'description' => 'Ermöglicht die Suspendierung einer Serverinstanz aufzuheben.',
                    'title' => 'Suspendierung des Servers aufheben',
                ],
                'view' => [
                    'title' => 'Einzelnen Server anzeigen',
                ],
            ],
            'service_management' => [
                'list' => [
                    'title' => 'Dienste auflisten',
                ],
                'title' => 'Serviceverwaltung',
                'view' => [
                    'title' => 'Einzelnen Dienst auflisten',
                ],
            ],
            'user_management' => [
                'create' => [
                    'description' => 'Erlaubt es neue Benutzer zu erstellen.',
                    'title' => 'Benutzer erstellen',
                ],
                'delete' => [
                    'description' => 'Erlaubt es einen Benutzer zu entfernen.',
                    'title' => 'Benutzer entfernen',
                ],
                'list' => [
                    'description' => 'Erlaubt die Auflistung aller Benutzerkonten.',
                    'title' => 'Benutzerkonten auflisten',
                ],
                'title' => 'Benutzerverwaltung',
                'update' => [
                    'description' => 'Erlaubt Benutzerdetails zu ändern (E-Mail, Passwort, TOPT Einstellungen).',
                    'title' => 'Benutzer aktualisieren',
                ],
                'view' => [
                    'description' => 'Erlaubt es Details zu einem Benutzer abzurufen. Inklusive aktiver Services.',
                    'title' => 'Einzelnen Benutzer anzeigen',
                ],
            ],
        ],
        'permissions' => [
            'admin' => [
                'location' => [
                    'list' => [
                        'desc' => 'Der Benutzer darf alle Standorte sehen.',
                        'title' => 'Liste Standorte',
                    ],
                ],
                'location_header' => 'Standort-Verwaltung',
                'node' => [
                    'create' => [
                        'desc' => 'Erlaubt es neue Nodes zu erstellen.',
                        'title' => 'Node erstellen',
                    ],
                    'delete' => [
                        'desc' => 'Erlaubt das löschen einer Node aus dem System.',
                        'title' => 'Node löschen',
                    ],
                    'list' => [
                        'desc' => 'Der Benutzer darf alle Nodes sehen.',
                        'title' => 'Nodes auflisten',
                    ],
                    'view-config' => [
                        'desc' => 'Der Benutzer kann die Konfiguration dieser Node sehen.',
                        'title' => 'Node Konfiguration anzeigen',
                    ],
                    'view' => [
                        'desc' => 'Erlaubt es Details zu einer Node abzurufen.',
                        'title' => 'Node anzeigen',
                    ],
                ],
                'node_header' => 'Nodeverwaltung',
                'option' => [
                    'list' => [
                        'title' => 'List Options',
                    ],
                    'view' => [
                        'title' => 'View Option',
                    ],
                ],
                'option_header' => 'Option Control',
                'pack' => [
                    'list' => [
                        'title' => 'List Packs',
                    ],
                    'view' => [
                        'title' => 'View Pack',
                    ],
                ],
                'pack_header' => 'Pack Control',
                'server' => [
                    'create' => [
                        'desc' => 'Der Benutzer darf Server erstellen.',
                        'title' => 'Server erstellen',
                    ],
                    'delete' => [
                        'desc' => 'Der Benutzer darf Server löschen.',
                        'title' => 'Server löschen',
                    ],
                    'edit-build' => [
                        'desc' => 'Der Benutzer darf Servereinstellungen bearbeiten.',
                        'title' => 'Servereinstellungen ändern',
                    ],
                    'edit-container' => [
                        'desc' => 'Der Benutzer darf die Container Einstellungen des Servers verändern.',
                        'title' => 'Server Container Einstellungen ändern',
                    ],
                    'edit-details' => [
                        'desc' => 'Der Benutzer darf die Servereinstellungen bearbeiten.',
                        'title' => 'Server Details ändern',
                    ],
                    'edit-startup' => [
                        'desc' => 'Der User darf die Startparameter ändern.',
                        'title' => 'Server Startparameter ändern',
                    ],
                    'install' => [
                        'desc' => 'Der Benutzer darf den Installationstatus bearbeiten',
                        'title' => 'Installlations Status ändern',
                    ],
                    'list' => [
                        'desc' => 'Der Benutzer darf alle Server dieser Instanz sehen.',
                        'title' => 'Servers Liste anzeigen',
                    ],
                    'rebuild' => [
                        'desc' => 'Der Benutzer darf den Server ner erstellen',
                        'title' => 'Rebuild Server',
                    ],
                    'suspend' => [
                        'desc' => 'Der User darf Server sperren.',
                        'title' => 'Server sperren',
                    ],
                    'view' => [
                        'desc' => 'Der Benutzer darf detaillierte Informationen zu allen Servern dieser Instanz sehen.',
                        'title' => 'Server Informationen anzeigen',
                    ],
                ],
                'server_header' => 'Server Control',
                'service' => [
                    'list' => [
                        'desc' => 'Der Benutzer kann alle Services sehen.',
                        'title' => 'Services anzeigen',
                    ],
                    'view' => [
                        'desc' => 'Der Benutzer kann detaillierte Informationen über einen Service sehen.',
                        'title' => 'Service anzeigen',
                    ],
                ],
                'service_header' => 'Service Control',
                'user' => [
                    'create' => [
                        'desc' => 'Der Benutzer kann einen User erstellen.',
                        'title' => 'Benutzer erstellen',
                    ],
                    'delete' => [
                        'desc' => 'Der User kann einen Server löschen.',
                        'title' => 'Benutzer löschen',
                    ],
                    'edit' => [
                        'desc' => 'Der User kann einen User bearbeiten.',
                        'title' => 'Aktualisiere Benutzer',
                    ],
                    'list' => [
                        'desc' => 'Ermöglicht die Auflistung aller derzeit im System befindlichen Benutzer.',
                        'title' => 'Benutzerliste anzeigen',
                    ],
                    'view' => [
                        'desc' => 'Der User kann detaillierte Informationen der User sehen.',
                        'title' => 'Benutzerinformationen anzeigen',
                    ],
                ],
                'user_header' => 'Benutzer Control',
            ],
            'user' => [
                'server' => [
                    'command' => [
                        'desc' => 'Der Benutzer hat Zugriff auf die Server Konsole.',
                        'title' => 'Befehl senden',
                    ],
                    'list' => [
                        'desc' => 'Der Benutzer darf seine Serverliste ansehen.',
                        'title' => 'Serverliste',
                    ],
                    'power' => [
                        'desc' => 'Der Benutzer darf den Server starten/stoppen/restartet.',
                        'title' => 'Server start/stop/restart',
                    ],
                    'view' => [
                        'desc' => 'Der Benutzer darf detaillierte Informationen über seine Server sehen.',
                        'title' => 'Serverinformationen anzeigen',
                    ],
                ],
                'server_header' => 'Benutzer Rechte',
            ],
        ],
    ],
    'confirm' => 'Bist du sicher?',
    'errors' => [
        '403' => [
            'desc' => 'Du bist nicht berechtigt, diese Seite zu öffnen.',
            'header' => 'Forbidden',
        ],
        '404' => [
            'desc' => 'Die angefragte Ressource konnte nicht gefunden werden.',
            'header' => 'File Not Found',
        ],
        'home' => 'Gehe zur Startseite',
        'installing' => [
            'desc' => 'Dieser Server wird derzeit noch installiert. Bitte versuche es in ein paar Minuten erneut, du solltest eine E-Mail erhalten, sobald dieser Prozess abgeschlossen ist.',
            'header' => 'Server Installation',
        ],
        'return' => 'Zur vorherigen Seite zurückkehren',
        'suspended' => [
            'desc' => 'Dieser Server wurde von einem Administrator gesperrt.',
            'header' => 'Server Suspended',
        ],
    ],
    'form_error' => 'Die folgenden Fehler sind bei dem Versuch die Anfrage auszuführen aufgetreten.',
    'index' => [
        'header' => 'Serverkonsole',
        'header_sub' => 'Kontrollieren Sie Ihren Server in Echtzeit.',
        'list' => 'Serverliste',
    ],
    'no_servers' => 'Deinem Benutzerkonto sind aktuell keine Server zugeordnet.',
    'password_req' => 'Passwörter müssen den folgenden Anforderungen genügen: mindestens ein Großbuchstabe, ein Kleinbuchstabe, eine Ziffer und eine Länge von mindestens 8 Zeichen.',
    'security' => [
        '2fa_checkpoint_help' => 'Verwende die 2FA-Anwendung auf deinem Telefon, um den QR-Codes auf der linken Seite zu scannen, oder gebe den Code darunter manuell ein. Sobald du dies getan hast, generiere einen Token und gebe ihn unten ein.',
        '2fa_disabled' => '2-Faktor-Authentifizierung ist deaktiviert! Du solltest die 2-Faktor-Authentifizierung aktivieren um dein Konto zusätzlich zu schützen.',
        '2fa_disable_error' => 'Der bereitgestellte 2FA-Token war nicht gültig. Der Schutz wurde für dieses Konto nicht deaktiviert.',
        '2fa_header' => '2-Faktor-Authentifizierung',
        '2fa_qr' => '2FA konfigurieren',
        '2fa_token_help' => 'Bitte gebe den 2FA Code von deiner 2FA APP ein (Google Authenticator, Authy, etc.).',
        'disable_2fa' => '2-Factor-Authentifizierung deaktivieren',
        'enable_2fa' => '2-Faktor-Authentifizierung aktivieren',
        'header' => 'Kontosicherheit',
        'header_sub' => 'Verwalte aktive Sitzungen und die 2-Faktor-Authentifizierung.',
        'sessions' => 'Aktive Sitzungen',
        'session_mgmt_disabled' => 'Der Administrator hat die Möglichkeit, aktive Sitzungen über dieses Panel zu verwalten, nicht aktiviert.',
    ],
    'server_name' => 'Name des Servers',
    'validation_error' => 'Es gab ein Problem mit einer oder mehreren deiner Eingaben.',
    'view_as_admin' => 'Du siehst die Serverliste als Administrator. Deshalb siehst du alle im System vorhandenen Server. Die Server bei denen du als Besitzer eingetragen bist sind mit einem blauen Punkt markiert.',
];
