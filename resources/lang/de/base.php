<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
 * Translated by Jakob Schrettenbrunner <dev@schrej.net>
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
    | Base Pterodactyl Language
    |--------------------------------------------------------------------------
    |
    | These base strings are used throughout the front-end of Pterodactyl but
    | not on pages that are used when viewing a server. Those keys are in server.php
    |
    */

    'validation_error' => 'Beim validieren der Daten ist ein Fehler aufgetreten:',

    'confirm' => 'Bist du sicher?',
    'failed' => 'Diese Zugangsdaten sind ungültig.',
    'throttle' => 'Zu viele Loginversuche. Bitte in :seconds Sekunden erneut versuchen.',
    'view_as_admin' => 'Du siehst die Serverliste als Administrator. Daher werden alle registrierten Server angezeigt. Server deren Eigentümer du bist sind mit einem blauen Punkt links neben dem Namen markiert.',
    'server_name' => 'Server Name',
    'no_servers' => 'Aktuell hast du keinen Zugriff auf irgendwelche server.',
    'form_error' => 'Folgende Fehler wurden beim Verarbeiten der Anfrage festgestellt.',
    'password_req' => 'Passwörter müssen folgende Anforderungen erfüllen: mindestens ein Klein- und Großbuchstabe, eine Ziffer und eine Länge von mindestens 8 Zeichen.',

    'account' => [
        'totp_header' => 'Zwei-Faktor Authentifizierung',
        'totp_qr' => 'TOTP QR Code',
        'totp_enable_help' => 'Anscheinend hast du Zwei-Faktor Authentifizierung deaktiviert. Diese Authentifizierungsmethode schützt dein Konto zusätzlich vor unauthorisierten Zugriffen. Wenn du es aktivierst wirst du bei jedem Login dazu aufgefordert ein TOTP Token einzugeben bevor du dich einloggen kannst. Dieses Token kann mit einem Smartphone oder anderen TOTP unterstützenden Gerät erzeugt werden.',
        'totp_apps' => 'Du benötigst eine TOTP unterstützende App (z.B. Google Authenticator, DUO Mobile, Authy, Enpass) um diese Option zu nutzen.',
        'totp_enable' => 'Aktiviere Zwei-Faktor Authentifizierung',
        'totp_disable' => 'Deaktiviere Zwei-Faktor Authentifizierung',
        'totp_token' => 'TOTP Token',
        'totp_disable_help' => 'Um Zwei-Faktor Authentifizierung zu deaktiveren musst du ein gültiges TOTP Token eingeben. Danach wird Zwei-Faktor Authentifizierung deaktivert.',
        'totp_checkpoint_help' => 'Bitte verifiziere deine TOTP Einstellungen indem du den QR Code mit einer App auf deinem Smartphone scannst und gebe die 6 Stellige Nummer im Eingabefeld unten ein. Drücke die Eingabetaste wenn du fertig bist.',
        'totp_enabled' => 'Zwei-Faktor Authentifizierung wurde erfolgreich aktiviert. Bitte schließe dieses Popup um den Vorgang abzuschließen.',
        'totp_enabled_error' => 'Das angegebene TOTP Token konnte nicht verifiziert werden. Bitte versuche es noch einmal.',

        'email_password' => 'Email Passwort',
        'update_user' => 'Benutzer aktualisieren',
        'delete_user' => 'Benutzer löschen',
        'update_email' => 'Email aktualisieren',
        'new_email' => 'Neue Email',
        'new_password' => 'Neues Passwort',
        'update_pass' => 'Passwort aktualisieren'

    ]

];
