import useSWR, { SWRConfiguration, SWRResponse } from 'swr';
import http, { FractalResponseList } from '@/api/http';
import { Transformers, SecurityKey } from '@definitions/user';
import { AxiosError } from 'axios';
import { base64Decode, bufferDecode, bufferEncode, decodeSecurityKeyCredentials } from '@/helpers';
import { LoginResponse } from '@/api/auth/login';
import useUserSWRContextKey from '@/plugins/useUserSWRContextKey';

const useSecurityKeys = (config?: SWRConfiguration<SecurityKey[], AxiosError>): SWRResponse<SecurityKey[], AxiosError> => {
    const key = useUserSWRContextKey([ 'account', 'security-keys' ]);

    return useSWR<SecurityKey[], AxiosError>(
        key,
        async (): Promise<SecurityKey[]> => {
            const { data } = await http.get('/api/client/account/security-keys');

            return (data as FractalResponseList).data.map(Transformers.toSecurityKey);
        },
        config,
    );
};

const deleteSecurityKey = async (uuid: string): Promise<void> => {
    await http.delete(`/api/client/account/security-keys/${uuid}`);
};

const registerCredentialForAccount = async (name: string, tokenId: string, credential: PublicKeyCredential): Promise<SecurityKey> => {
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

    return Transformers.toSecurityKey(data);
};

const registerSecurityKey = async (name: string): Promise<SecurityKey> => {
    const { data } = await http.get('/api/client/account/security-keys/register');

    const publicKey = data.data.credentials;
    publicKey.challenge = bufferDecode(base64Decode(publicKey.challenge));
    publicKey.user.id = bufferDecode(publicKey.user.id);

    if (publicKey.excludeCredentials) {
        publicKey.excludeCredentials = decodeSecurityKeyCredentials(publicKey.excludeCredentials);
    }

    const credentials = await navigator.credentials.create({ publicKey });

    if (!credentials || credentials.type !== 'public-key') {
        throw new Error(`Unexpected type returned by navigator.credentials.create(): expected "public-key", got "${credentials?.type}"`);
    }

    return await registerCredentialForAccount(name, data.data.token_id, credentials);
};

// eslint-disable-next-line camelcase
const authenticateSecurityKey = async (data: { confirmation_token: string; data: string }): Promise<LoginResponse> => {
    const response = await http.post('/auth/login/checkpoint/key', data);

    return {
        complete: response.data.complete,
        intended: response.data.data?.intended || null,
    };
};

export { useSecurityKeys, deleteSecurityKey, registerSecurityKey, authenticateSecurityKey };
