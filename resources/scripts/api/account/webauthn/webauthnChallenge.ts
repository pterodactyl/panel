import http from '@/api/http';
import { LoginResponse } from '@/api/auth/login';
import { base64Decode, bufferDecode, bufferEncode, decodeCredentials } from '@/api/account/webauthn/registerWebauthnKey';

export default (token: string, publicKey: PublicKeyCredentialRequestOptions): Promise<LoginResponse> => {
    return new Promise((resolve, reject) => {
        console.log(token);
        console.log(publicKey);
        const publicKeyCredential = Object.assign({}, publicKey);

        publicKeyCredential.challenge = bufferDecode(base64Decode(publicKey.challenge.toString()));
        if (publicKey.allowCredentials) {
            publicKeyCredential.allowCredentials = decodeCredentials(publicKey.allowCredentials);
        }

        navigator.credentials.get({
            publicKey: publicKeyCredential,
        }).then((c) => {
            if (c === null) {
                return;
            }
            const credential = c as PublicKeyCredential;
            const response = credential.response as AuthenticatorAssertionResponse;

            const data = {
                confirmation_token: token,

                data: JSON.stringify({
                    id: credential.id,
                    type: credential.type,
                    rawId: bufferEncode(credential.rawId),

                    response: {
                        authenticatorData: bufferEncode(response.authenticatorData),
                        clientDataJSON: bufferEncode(response.clientDataJSON),
                        signature: bufferEncode(response.signature),
                        userHandle: response.userHandle ? bufferEncode(response.userHandle) : null,
                    },
                }),
            };
            console.log(data);

            http.post('/auth/login/checkpoint/key', data).then(response => {
                return resolve({
                    complete: response.data.complete,
                    intended: response.data.data?.intended || undefined,
                });
            }).catch(reject);
        }).catch(reject);
    });
};
