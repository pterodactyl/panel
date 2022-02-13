import useSWR, { SWRConfiguration, SWRResponse } from 'swr';
import { useStoreState } from '@/state/hooks';
import http, { FractalResponseList } from '@/api/http';
import Transformers from '@transformers';
import { SecurityKey } from '@models';
import { AxiosError } from 'axios';

const useSecurityKeys = (config?: SWRConfiguration<SecurityKey[], AxiosError>): SWRResponse<SecurityKey[], AxiosError> => {
    const uuid = useStoreState(state => state.user.data!.uuid);

    return useSWR<SecurityKey[], AxiosError>(
        [ 'account', uuid, 'security-keys' ],
        async (): Promise<SecurityKey[]> => {
            const { data } = await http.get('/api/client/account/security-keys');

            return (data as FractalResponseList).data.map((datum) => Transformers.toSecurityKey(datum.attributes));
        },
        config,
    );
};

export { useSecurityKeys };
