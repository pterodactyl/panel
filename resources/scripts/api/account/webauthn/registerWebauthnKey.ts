import http from '@/api/http';

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

export const bufferDecode = (value: string): ArrayBuffer => Uint8Array.from(window.atob(value), c => c.charCodeAt(0));

// @ts-ignore
export const bufferEncode = (value: ArrayBuffer): string => window.btoa(String.fromCharCode.apply(null, new Uint8Array(value)));

export const decodeCredentials = (credentials: PublicKeyCredentialDescriptor[]) => {
    return credentials.map(c => {
        return {
            id: bufferDecode(base64Decode(c.id.toString())),
            type: c.type,
            transports: c.transports,
        };
    });
};

const registerCredentialForAccount = async (name: string, tokenId: string, credential: PublicKeyCredential) => {
    const { data } = await http.post('/api/client/account/security-keys/register', {
        name,
        token_id: tokenId,
        registration: {
            id: credential.id,
            type: credential.type,
            rawId: bufferEncode(credential.rawId),
            response: {
                attestationObject: bufferEncode((credential.response as AuthenticatorAttestationResponse).attestationObject),
                clientDataJSON: bufferEncode(credential.response.clientDataJSON),
            },
        },
    });

    console.log(data.data);
};

export const register = async (name: string): Promise<void> => {
    const { data } = await http.get('/api/client/account/security-keys/register');

    const publicKey = data.data.credentials;
    publicKey.challenge = bufferDecode(base64Decode(publicKey.challenge));
    publicKey.user.id = bufferDecode(publicKey.user.id);

    if (publicKey.excludeCredentials) {
        publicKey.excludeCredentials = decodeCredentials(publicKey.excludeCredentials);
    }

    const credentials = await navigator.credentials.create({ publicKey });

    if (!credentials || credentials.type !== 'public-key') {
        throw new Error(`Unexpected type returned by navigator.credentials.create(): expected "public-key", got "${credentials?.type}"`);
    }

    await registerCredentialForAccount(name, data.data.token_id, credentials);
};
