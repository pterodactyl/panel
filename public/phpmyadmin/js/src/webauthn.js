/**
 * @param {ArrayBuffer} buffer
 *
 * @return {string}
 */
const arrayBufferToBase64 = buffer => {
    const bytes = new Uint8Array(buffer);
    let string = '';
    for (const byte of bytes) {
        string += String.fromCharCode(byte);
    }

    return window.btoa(string);
};

/**
 * @param {string} string
 *
 * @return {Uint8Array}
 */
const base64ToUint8Array = string => {
    return Uint8Array.from(window.atob(string), char => char.charCodeAt(0));
};

/**
 * @param {JQuery<HTMLElement>} $input
 *
 * @return {void}
 */
const handleCreation = $input => {
    const $form = $input.parents('form');
    $form.find('input[type=submit]').hide();

    const creationOptionsJson = $input.attr('data-creation-options');
    const creationOptions = JSON.parse(creationOptionsJson);

    const publicKey = creationOptions;
    publicKey.challenge = base64ToUint8Array(creationOptions.challenge);
    publicKey.user.id = base64ToUint8Array(creationOptions.user.id);
    if (creationOptions.excludeCredentials) {
        const excludedCredentials = [];
        for (let value of creationOptions.excludeCredentials) {
            let excludedCredential = value;
            excludedCredential.id = base64ToUint8Array(value.id);
            excludedCredentials.push(excludedCredential);
        }
        publicKey.excludeCredentials = excludedCredentials;
    }

    // eslint-disable-next-line compat/compat
    navigator.credentials.create({ publicKey: publicKey })
        .then((credential) => {
            const credentialJson = JSON.stringify({
                id: credential.id,
                rawId: arrayBufferToBase64(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: arrayBufferToBase64(credential.response.clientDataJSON),
                    attestationObject: arrayBufferToBase64(credential.response.attestationObject),
                }
            });
            $input.val(credentialJson);
            $form.trigger('submit');
        })
        .catch((error) => Functions.ajaxShowMessage(error, false, 'error'));
};

/**
 * @param {JQuery<HTMLElement>} $input
 *
 * @return {void}
 */
const handleRequest = $input => {
    const $form = $input.parents('form');
    $form.find('input[type=submit]').hide();

    const requestOptionsJson = $input.attr('data-request-options');
    const requestOptions = JSON.parse(requestOptionsJson);

    const publicKey = requestOptions;
    publicKey.challenge = base64ToUint8Array(requestOptions.challenge);
    if (requestOptions.allowCredentials) {
        const allowedCredentials = [];
        for (let value of requestOptions.allowCredentials) {
            let allowedCredential = value;
            allowedCredential.id = base64ToUint8Array(value.id);
            allowedCredentials.push(allowedCredential);
        }
        publicKey.allowCredentials = allowedCredentials;
    }

    // eslint-disable-next-line compat/compat
    navigator.credentials.get({ publicKey: publicKey })
        .then((credential) => {
            const credentialJson = JSON.stringify({
                id: credential.id,
                rawId: arrayBufferToBase64(credential.rawId),
                type: credential.type,
                response: {
                    authenticatorData: arrayBufferToBase64(credential.response.authenticatorData),
                    clientDataJSON: arrayBufferToBase64(credential.response.clientDataJSON),
                    signature: arrayBufferToBase64(credential.response.signature),
                    userHandle: arrayBufferToBase64(credential.response.userHandle),
                }
            });
            $input.val(credentialJson);
            $form.trigger('submit');
        })
        .catch((error) => Functions.ajaxShowMessage(error, false, 'error'));
};

AJAX.registerOnload('webauthn.js', function () {
    if (
        ! navigator.credentials
        || ! navigator.credentials.create
        || ! navigator.credentials.get
        || ! window.PublicKeyCredential
    ) {
        Functions.ajaxShowMessage(Messages.webAuthnNotSupported, false, 'error');

        return;
    }

    const $creationInput = $('#webauthn_creation_response');
    if ($creationInput.length > 0) {
        handleCreation($creationInput);
    }

    const $requestInput = $('#webauthn_request_response');
    if ($requestInput.length > 0) {
        handleRequest($requestInput);
    }
});
