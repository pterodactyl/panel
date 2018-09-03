<?php

return [
    'account' => [
        'current_password' => 'Praegune salasõna',
        'delete_user' => 'Kustuta kasutaja',
        'email_password' => 'Emaili salasõna',
        'exception' => 'Kasutaja uuendamisel tekkis viga',
        'first_name' => 'Eesnimi',
        'header' => 'Kasutaja haldamine',
        'header_sub' => 'Muuda oma kasutaja seadeid',
        'invalid_pass' => 'Sisestatud salasõna on väär',
        'last_name' => 'Perekonnanimi',
        'new_email' => 'Uus e-maili aadress',
        'new_password' => 'Uus salasõna',
        'new_password_again' => 'Korda uut salasõna',
        'totp_apps' => 'Sul peab olema TOTP toetav applikatsioon (nt Google Authenticator,DUO Mobile,Authy,Enpass), et kasutada seda',
        'totp_checkpoint_help' => 'Palun kinnita oma TOTP seaded skänneerides QR kood ja sisesta 6 numbriline kood',
        'totp_disable' => 'Keela 2-tasemeline autentimine',
        'totp_disable_help' => 'TOTP keelamiseks peate sisestama TOTP tokeni. Pärast kinnitust lülitatakse sellel kasutajal TOTP välja. ',
        'totp_enable' => 'Luba 2-astmeline autentimine',
        'totp_enabled' => 'Teie kasutaja on aktiveeritud TOTP kaudu. Palun väljuge, et lõpetada',
        'totp_enabled_error' => 'TOTP tokenit ei olnud võimalik kontrollida. Palun proovige uuesti.',
        'totp_enable_help' => 'Paistab, et Teil pole 2-astmeline autentimine aktiveeritud. See meetod lisab teie kasutajale turvalisust juurde. Selle aktiveerides peate te sisestama oma moblasse koodi, enne kui logite sisse',
        'totp_header' => '2-astmeline autentimine',
        'totp_qr' => 'TOTP QR kood',
        'totp_token' => 'TOTP Token',
        'update_email' => 'Uuenda email',
        'update_identitity' => 'Uuenda informatsiooni',
        'update_pass' => 'Uuenda salasõna',
        'update_user' => 'Uuenda kasutajat ',
        'username_help' => 'Sinu kasutajanimi peab olema unikaalne sinu kasutajale ja võib ainult sisaldada järgmiseid märke: :requirements',
    ],
    'api' => [
        'index' => [
            'create_new' => 'Loo uus API võti ',
            'header' => 'API Ligipääs',
            'header_sub' => 'Halda oma API võtmeid',
            'list' => 'API Võtmed',
        ],
        'new' => [
            'allowed_ips' => [
                'description' => 'Siesta nimekiri IP-dest, mis võivad ühendada API-sse selle võtmega. CIDR kasutamine on lubatud. Jäta tühjaks, et lubada igat IP-d',
                'title' => 'Lubatud IP-d',
            ],
            'base' => [
                'information' => [
                    'description' => 'Tagastab nimekirja serveritest, millega see kasutaja seotud on',
                    'title' => 'Baas informatsioon',
                ],
                'title' => 'Baas informatsioon',
            ],
            'descriptive_memo' => [
                'description' => 'Sisesta lühikirjeldus milleks uut API võtit kasutatakse ',
                'title' => 'Meeldetuletus',
            ],
            'form_title' => 'Detailid',
            'header' => 'Uus API võti',
            'header_sub' => 'Loo uus API võti',
            'location_management' => [
                'list' => [
                    'description' => 'Lubab kõikide kohtade ja nendega seotud nodede kuvamist',
                    'title' => 'Kuva asukohad',
                ],
                'title' => 'Asukoha seaded',
            ],
            'node_management' => [
                'allocations' => [
                    'description' => 'Lubab kõikide asukohtade kõvamist kõikide nodede puhul',
                    'title' => 'Kuva allokeerimised',
                ],
                'create' => [
                    'description' => 'Lubab uue node loomist süsteemis',
                    'title' => 'Loo node',
                ],
                'delete' => [
                    'description' => 'Lubab node kustutamist',
                    'title' => 'Kustuta node',
                ],
                'list' => [
                    'description' => 'Lubab kõigi node näitamist ',
                    'title' => 'Kuva noded',
                ],
                'title' => 'Node kontrollimine',
                'view' => [
                    'description' => 'Lubab vaadata detaile spetsiifilise node kohta, sealhulgas ka aktiivseid teenuseid',
                    'title' => 'Kuva üksik node',
                ],
            ],
            'server_management' => [
                'build' => [
                    'description' => 'Lubab muuta serveri ehitamise parameetreid näiteks nagu: RAM, CPU, kõvaketta hulk ja tavaline IP',
                    'title' => 'Uuenda ehitust',
                ],
                'config' => [
                    'description' => 'Lubab muuta serveri seadeid (nimi, omanik ja ligipääsu token)',
                ],
                'create' => [
                    'description' => 'Lubab uue serveri loomist süsteemis ',
                ],
                'power' => [
                    'title' => 'Serveri seis',
                ],
                'unsuspend' => [
                    'description' => 'Lubab serveri taastamist pausilt',
                ],
                'view' => [
                    'description' => 'Lubab info kuvamist koos daemon_token, ning teiste protsesside kohta',
                    'title' => 'Näita ühte serverit ',
                ],
            ],
            'service_management' => [
                'list' => [
                    'description' => 'Lubab kõikide teenuste kuvamist süsteemis ',
                    'title' => 'Kuva teenused',
                ],
                'title' => 'Teenuste haldamine',
                'view' => [
                    'description' => 'Lubab iga teenuse kohta detailse info kuvamist. Sealhulgas erinevad valikud ja seadistused ',
                    'title' => 'Kuva üksik teenus',
                ],
            ],
            'user_management' => [
                'create' => [
                    'description' => 'Lubab uue kasutaja loomist süsteemis',
                    'title' => 'Loo kasutaja',
                ],
                'delete' => [
                    'description' => 'Lubab kasutaja kustutamist',
                    'title' => 'Kustuta kasutaja',
                ],
                'list' => [
                    'description' => 'Lubab kõikide kasutajate kuvamist süsteemis',
                    'title' => 'Näita kasutajaid ',
                ],
                'title' => 'Kasutaja haldamine',
                'update' => [
                    'description' => 'Lubab kasutaja info muutumist (email, salasõna, TOTP) ',
                    'title' => 'Uuenda kasutajat',
                ],
                'view' => [
                    'description' => 'Lubab kasutaja detailide kuvamist aktiivsete teenuste puhul',
                    'title' => 'Kuva üksik kasutaja',
                ],
            ],
        ],
    ],
    'confirm' => 'Oled sa kindel?',
    'errors' => [
        '404' => [
            'desc' => 'Me ei suutnud leida vajalikku faili serverist ',
        ],
        'home' => 'Mine pealehele',
        'return' => 'Tagasi eelmisele lehele ',
    ],
    'form_error' => 'Järgmised vead tekkisid eelmise ülesande  täitmisel',
    'index' => [
        'header' => 'Serveri konsool',
        'header_sub' => 'Kontrolli oma serverit reaalajas',
        'list' => 'Serverite nimekiri',
    ],
    'no_servers' => 'Hetkel ei ole ühtegi serverit teie kasutaja all',
    'password_req' => 'Salasõna peab täitma järgmiseid nõudeid: Üks suur täht, üks väike täht, üks number ja miinimum pikkusega 8',
    'security' => [
        '2fa_checkpoint_help' => 'Kasuta 2-astmelise autentimise applikatsiooni, et oma moblaga teha pilti või sisesta kood käsitsi. Pärast seda genereeri token ja sisesta see',
        '2fa_disabled' => '2-astmeline autentimine on sellel kasutajal maas. Ekstra turvalisuse tagamiseks peaksid sa lisama 2-astmelise autentimise',
        '2fa_header' => '2-astmeline autentimine',
        '2fa_qr' => 'Seadista 2-astmeline autentimine oma seadmes',
        'enable_2fa' => 'Luba 2-astmeline autentimine',
        'header' => 'Turvaseaded',
        'header_sub' => 'Kontrolli ühendusi ja 2-astmelist autentimist',
        'sessions' => 'Aktiivsed ühendused',
    ],
    'server_name' => 'Serveri nimi',
    'view_as_admin' => 'Te vaatate seda serveri listi administraatorina. Selle tõttu kõik installeeritud serverid sellel süsteemil on nähtavad. Teie omatud serveritel on sinine täpp serveri nime kõrval. ',
];
