import http from '@/api/http';
import { Key, rawDataToKey } from '@/api/account/webauthn/getWebauthn';

export const base64Decode = (input: string): string => {
    input = input.replace(/-/g, '+').replace(/_/g, '/');
    const pad = input.length % 4;
    if (pad) {
        if (pad === 1) {
            throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
        }
        input += new Array(5 - pad).join('=');
    }
    return input;
};

export const bufferDecode = (value: string): ArrayBuffer => {
    return Uint8Array.from(window.atob(value), c => c.charCodeAt(0));
};

export const bufferEncode = (value: ArrayBuffer): string => {
    // @ts-ignore
    return window.btoa(String.fromCharCode.apply(null, new Uint8Array(value)));
};

export const decodeCredentials = (credentials: PublicKeyCredentialDescriptor[]) => {
    return credentials.map(c => {
        return {
            id: bufferDecode(base64Decode(c.id.toString())),
            type: c.type,
            transports: c.transports,
        };
    });
};

export default (name: string): Promise<Key> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/webauthn/register').then((res) => {
            const publicKey = res.data.public_key;
            const publicKeyCredential = Object.assign({}, publicKey);

            publicKeyCredential.user.id = bufferDecode(publicKey.user.id);
            publicKeyCredential.challenge = bufferDecode(base64Decode(publicKey.challenge));
            if (publicKey.excludeCredentials) {
                publicKeyCredential.excludeCredentials = decodeCredentials(publicKey.excludeCredentials);
            }

            return navigator.credentials.create({
                publicKey: publicKeyCredential,
            });
        }).then((c) => {
            if (c === null) {
                return;
            }
            const credential = c as PublicKeyCredential;
            const response = credential.response as AuthenticatorAttestationResponse;

            http.post('/api/client/account/webauthn/register', {
                name: name,

                register: JSON.stringify({
                    id: credential.id,
                    type: credential.type,
                    rawId: bufferEncode(credential.rawId),

                    response: {
                        attestationObject: bufferEncode(response.attestationObject),
                        clientDataJSON: bufferEncode(response.clientDataJSON),
                    },
                }),
            }).then(({ data }) => resolve(rawDataToKey(data.attributes))).catch(reject);
        }).catch(reject);
    });
};
