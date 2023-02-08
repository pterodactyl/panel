import type { AxiosError } from 'axios';
import type { SWRConfiguration } from 'swr';
import useSWR from 'swr';

import type { SecurityKey } from '@definitions/user';
import { Transformers } from '@definitions/user';
import { LoginResponse } from '@/api/auth/login';
import type { FractalResponseList } from '@/api/http';
import http from '@/api/http';
import { decodeBase64 } from '@/lib/base64';
import { decodeBuffer, encodeBuffer } from '@/lib/buffer';
import { useUserSWRKey } from '@/plugins/useSWRKey';

function decodeSecurityKeyCredentials(credentials: PublicKeyCredentialDescriptor[]) {
    return credentials.map(c => ({
        id: decodeBuffer(decodeBase64(c.id.toString())),
        type: c.type,
        transports: c.transports,
    }));
}

function useSecurityKeys(config?: SWRConfiguration<SecurityKey[], AxiosError>) {
    const key = useUserSWRKey(['account', 'security-keys']);

    return useSWR<SecurityKey[], AxiosError>(
        key,
        async (): Promise<SecurityKey[]> => {
            const { data } = await http.get('/api/client/account/security-keys');

            return (data as FractalResponseList).data.map(datum => Transformers.toSecurityKey(datum.attributes));
        },
        { revalidateOnMount: false, ...(config ?? {}) },
    );
}

async function deleteSecurityKey(uuid: string): Promise<void> {
    await http.delete(`/api/client/account/security-keys/${uuid}`);
}

async function registerCredentialForAccount(
    name: string,
    tokenId: string,
    credential: PublicKeyCredential,
): Promise<SecurityKey> {
    const { data } = await http.post('/api/client/account/security-keys/register', {
        name,
        token_id: tokenId,
        registration: {
            id: credential.id,
            type: credential.type,
            rawId: encodeBuffer(credential.rawId),
            response: {
                attestationObject: encodeBuffer(
                    (credential.response as AuthenticatorAttestationResponse).attestationObject,
                ),
                clientDataJSON: encodeBuffer(credential.response.clientDataJSON),
            },
        },
    });

    return Transformers.toSecurityKey(data.attributes);
}

async function registerSecurityKey(name: string): Promise<SecurityKey> {
    const { data } = await http.get('/api/client/account/security-keys/register');

    const publicKey = data.data.credentials;
    publicKey.challenge = decodeBuffer(decodeBase64(publicKey.challenge));
    publicKey.user.id = decodeBuffer(publicKey.user.id);

    if (publicKey.excludeCredentials) {
        publicKey.excludeCredentials = decodeSecurityKeyCredentials(publicKey.excludeCredentials);
    }

    const credentials = await navigator.credentials.create({ publicKey });
    if (!credentials || credentials.type !== 'public-key') {
        throw new Error(
            `Unexpected type returned by navigator.credentials.create(): expected "public-key", got "${credentials?.type}"`,
        );
    }

    return await registerCredentialForAccount(name, data.data.token_id, credentials as PublicKeyCredential);
}

// eslint-disable-next-line camelcase
async function authenticateSecurityKey(data: { confirmation_token: string; data: string }): Promise<LoginResponse> {
    const response = await http.post('/auth/login/checkpoint/key', data);

    return {
        complete: response.data.complete,
        intended: response.data.data?.intended || null,
    };
}

export { useSecurityKeys, deleteSecurityKey, registerSecurityKey, authenticateSecurityKey };
