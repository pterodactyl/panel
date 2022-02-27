import useSWR, { SWRConfiguration, SWRResponse } from 'swr';
import http, { FractalResponseList } from '@/api/http';
import { Transformers, PersonalAccessToken } from '@definitions/user';
import { AxiosError } from 'axios';
import useUserSWRContextKey from '@/plugins/useUserSWRContextKey';

const useAPIKeys = (
    config?: SWRConfiguration<PersonalAccessToken[], AxiosError>,
): SWRResponse<PersonalAccessToken[], AxiosError> => {
    const key = useUserSWRContextKey([ 'account', 'api-keys' ]);

    return useSWR(key, async () => {
        const { data } = await http.get('/api/client/account/api-keys');

        return (data as FractalResponseList).data.map((datum: any) => {
            return Transformers.toPersonalAccessToken(datum.attributes);
        });
    }, config || { revalidateOnMount: false });
};

const createAPIKey = async (description: string): Promise<[ PersonalAccessToken, string ]> => {
    const { data } = await http.post('/api/client/account/api-keys', { description });

    const token = Transformers.toPersonalAccessToken(data);

    return [ token, data.meta?.secret_token || '' ];
};

const deleteAPIKey = async (identifier: string) =>
    await http.delete(`/api/client/account/api-keys/${identifier}`);

export { useAPIKeys, createAPIKey, deleteAPIKey };
