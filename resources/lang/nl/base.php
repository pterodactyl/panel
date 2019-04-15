<?php

return [
    'validation_error' => 'Er is een fout opgetreden tijdens het valideren van de data die u heeft opgegeven.',
    'errors' => [
        'return' => 'Keer terug naar de vorige pagina',
        'home' => 'Naar Home',
        '403' => [
            'header' => 'Geen toegang',
            'desc' => 'U heeft geen rechten voor toegang tot deze bron.',
        ],
        '404' => [
            'header' => 'Bestand niet gevonden.',
            'desc' => 'We konden de opgevraagde bron niet vinden op de server.',
        ],
        'installing' => [
            'header' => 'Server Installeren',
            'desc' => 'De gevraagde server is nog bezig met het voltooien van het installatieproces. Probeer het over een paar minuten opnieuw, u ontvangt een e-mail zodra dit proces is voltooid.',
        ],
        'suspended' => [
            'header' => 'Server Opgeschort',
            'desc' => 'Deze server is opgeschort en kan niet worden geopend.',
        ],
        'maintenance' => [
            'header' => 'Node Onder Onderhoud',
            'title' => 'Tijdelijk Onbeschikbaar',
            'desc' => 'Deze node is onder onderhoud, daarom kan uw server tijdelijk niet worden geopend.',
        ],
    ],
    'index' => [
        'header' => 'Uw Servers',
        'header_sub' => 'Servers waar u toegang tot heeft.',
        'list' => 'Server Lijst',
    ],
    'api' => [
        'index' => [
            'list' => 'API sleutels',
            'header' => 'API toegang',
            'header_sub' => 'Beheer jouw API sleutels.',
            'create_new' => 'Creëer nieuwe API sleutel',
            'keypair_created' => 'Een API sleutel is gegenereerd en wordt hieronder weergegeven.',
        ],
        'new' => [
            'header' => 'Nieuwe API Sleutel',
            'header_sub' => 'Maak een nieuwe API toegangs sleutel.',
            'form_title' => 'Details',
            'descriptive_memo' => [
                'title' => 'Beschrijving',
                'description' => 'Voeg een korte beschrijving toe als referentie waarvoor deze API sleutel gebruikt zal worden.',
            ],
            'allowed_ips' => [
                'title' => 'Toegestane IP\'s',
                'description' => 'Voer een door regels gescheiden lijst in van IP\'s die toegang hebben tot de API met behulp van deze sleutel. CIDR-notatie is toegestaan. Laat dit leeg om alle IP-adressen toe te staan.',
            ],
        ],
    ],
    'account' => [
        'details_updated' => 'Je account details zijn succesvol veranderd',
        'invalid_password' => 'Het opgegeven wachtwoord voor je account is ongeldig.',
        'header' => 'Account Beheer',
        'header_sub' => 'Beheer uw account details',
        'update_pass' => 'Wachtwoord Bijwerken',
        'update_email' => 'E-mail Adres Bijwerken',
        'current_password' => 'Huidig Wachtwoord',
        'new_password' => 'Nieuw Wachtwoord',
        'new_password_again' => 'Herhaal nieuw wachtwoord',
        'new_email' => 'Nieuw e-mail adres',
        'first_name' => 'Voornaam',
        'last_name' => 'Achternaam',
        'update_identitity' => 'Identiteit Bijwerken',
        'username_help' => 'Uw gebruikersnaam moet uniek zijn en mag enkel de volgende characters bevatten: :requirements.',
        'language' => 'Taal',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Uw host heeft de mogelijkheid om accountsessies via deze interface te beheren niet ingeschakeld.',
        'header' => 'Accountbeveiliging',
        'header_sub' => 'Beheer actieve sessies en 2-Factor Authenticatie.',
        'sessions' => 'Actieve Sessies',
        '2fa_header' => '2-factor Authenticatie',
        '2fa_token_help' => 'Voer de 2FA-token in die door uw app is gegenereerd (Google Authenticator, Authy, enz.).',
        'disable_2fa' => 'Schakel 2-Factor Authenticatie uit',
        '2fa_enabled' => '2-Factor Authenticatie is ingeschakeld voor dit account en is vereist om in te loggen bij het paneel. Als u 2FA wilt uitschakelen, voert u hieronder een geldig token in en verzendt u het formulier.',
        '2fa_disabled' => '2-Factor Authenticatie is uitgeschakeld voor uw account! Schakel 2FA in om een extra beveiligingsniveau toe te voegen aan uw account.',
        'enable_2fa' => 'Zet 2-Factor Authenticatie aan',
        '2fa_qr' => 'Configureer 2FA op uw toestel',
        '2fa_checkpoint_help' => 'Gebruik de 2FA-applicatie op uw telefoon om een foto van de QR-code aan de linkerkant te maken, of voer handmatig de code in. Zodra u dit hebt gedaan, genereert u een token en voert u dit hieronder in.',
        '2fa_disable_error' => 'De opgegeven 2FA-token was niet geldig. 2FA is niet uitgeschakeld voor dit account.',
    ],
];
