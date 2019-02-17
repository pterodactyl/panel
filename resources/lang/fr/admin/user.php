<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'exceptions' => [
        'user_has_servers' => 'Ne peu pas effacer un utilisateur qui a des serveurs actifs relier a son compte. Veuillez effacer leurs serveurs avant de continuer.',
        'empty_oauth2_id' => 'Vous devez fournir un ID OAuth2 valide pour convertir ce scompte en un compte OAuth2.',
    ],
    'notices' => [
        'account_created' => 'Le compte a été créé avec succès.',
        'account_updated' => 'Le compte a été mis a jour avec succès.',
    ],
    'convert_description' => 'Convertir ce compte en un compte OAuth2 ou en un compte normal, ceci va modifier leur moyen de connexion à OAuth2 ou de nouveau à nom d\'utilisateur/email et mot de passe. Si vous reconvertez le compte vers un compte normal un email sera envoyé avec un lien pour modifier le mot de passe.',
    'convert_to_oauth2' => 'Convertir En Un Compte OAuth2',
    'convert_to_normal' => 'Convertir En Un Compte Normal',
];
