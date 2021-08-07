<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LaravelWebauthn Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable LaravelWebauthn.
    |
    */

    'enable' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to Webauthn routes, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        'auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefix path
    |--------------------------------------------------------------------------
    |
    | The uri prefix for all webauthn requests.
    |
    */

    'prefix' => 'webauthn',

    'authenticate' => [
        /*
        |--------------------------------------------------------------------------
        | View to load after middleware login request.
        |--------------------------------------------------------------------------
        |
        | The name of blade template to load whe a user login and it request to validate
        | the Webauthn 2nd factor.
        |
        */
        'view' => 'webauthn::authenticate',

        /*
        |--------------------------------------------------------------------------
        | Redirect with callback url after login.
        |--------------------------------------------------------------------------
        |
        | Save the destination url, then after a successful login, redirect to this
        | url.
        |
        */
        'postSuccessCallback' => true,

        /*
        |--------------------------------------------------------------------------
        | Redirect route
        |--------------------------------------------------------------------------
        |
        | If postSuccessCallback if false, redirect to this route after login
        | request is complete.
        | If empty, send a json response to let the client side redirection.
        |
        */
        'postSuccessRedirectRoute' => '',
    ],

    'register' => [
        /*
        |--------------------------------------------------------------------------
        | View to load on register request.
        |--------------------------------------------------------------------------
        |
        | The name of blade template to load when a user request a creation of
        | Webauthn key.
        |
        */
        'view' => 'webauthn::register',

        /*
        |--------------------------------------------------------------------------
        | Redirect route
        |--------------------------------------------------------------------------
        |
        | The route to redirect to after register key request is complete.
        | If empty, send a json response to let the client side redirection.
        |
        */
        'postSuccessRedirectRoute' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session name
    |--------------------------------------------------------------------------
    |
    | Name of the session parameter to store the successful login.
    |
    */

    'sessionName' => 'webauthn_auth',

    /*
    |--------------------------------------------------------------------------
    | Webauthn challenge length
    |--------------------------------------------------------------------------
    |
    | Length of the random string used in the challenge request.
    |
    */

    'challenge_length' => 32,

    /*
    |--------------------------------------------------------------------------
    | Webauthn timeout (milliseconds)
    |--------------------------------------------------------------------------
    |
    | Time that the caller is willing to wait for the call to complete.
    |
    */

    'timeout' => 60000,

    /*
    |--------------------------------------------------------------------------
    | Webauthn extension client input
    |--------------------------------------------------------------------------
    |
    | Optional authentication extension.
    | See https://www.w3.org/TR/webauthn/#client-extension-input
    |
    */

    'extensions' => [],

    /*
    |--------------------------------------------------------------------------
    | Webauthn icon
    |--------------------------------------------------------------------------
    |
    | Url which resolves to an image associated with the entity.
    | See https://www.w3.org/TR/webauthn/#dom-publickeycredentialentity-icon
    |
    */

    'icon' => null,

    /*
    |--------------------------------------------------------------------------
    | Webauthn Attestation Conveyance
    |--------------------------------------------------------------------------
    |
    | This parameter specify the preference regarding the attestation conveyance
    | during credential generation.
    | See https://www.w3.org/TR/webauthn/#attestation-convey
    |
    */

    'attestation_conveyance' => \Webauthn\PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,

    /*
    |--------------------------------------------------------------------------
    | Google Safetynet ApiKey
    |--------------------------------------------------------------------------
    |
    | Api key to use Google Safetynet.
    | See https://developer.android.com/training/safetynet/attestation
    |
    */

    'google_safetynet_api_key' => '',

    /*
    |--------------------------------------------------------------------------
    | Webauthn Public Key Credential Parameters
    |--------------------------------------------------------------------------
    |
    | List of allowed Cryptographic Algorithm Identifier.
    | See https://www.w3.org/TR/webauthn/#alg-identifier
    |
    */

    'public_key_credential_parameters' => [
        \Cose\Algorithms::COSE_ALGORITHM_ES256,
        \Cose\Algorithms::COSE_ALGORITHM_RS256,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webauthn Authenticator Selection Criteria
    |--------------------------------------------------------------------------
    |
    | Requirement for the creation operation.
    | See https://www.w3.org/TR/webauthn/#authenticatorSelection
    |
    */

    'authenticator_selection_criteria' => [
        /*
        | See https://www.w3.org/TR/webauthn/#attachment
        */
        'attachment_mode' => \Webauthn\AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,

        'require_resident_key' => false,

        /*
        | See https://www.w3.org/TR/webauthn/#userVerificationRequirement
        */
        'user_verification' => \Webauthn\AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
    ],
];
