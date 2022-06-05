import useUserSWRContentKey from '@/plugins/useUserSWRContentKey';
import useSWR, { ConfigInterface, responseInterface } from 'swr';
import { ActivityLog, Transformers } from '@definitions/user';
import { AxiosError } from 'axios';
import http, { PaginatedResult } from '@/api/http';
import { toPaginatedSet } from '@definitions/helpers';

const useActivityLogs = (page = 1, config?: ConfigInterface<PaginatedResult<ActivityLog>, AxiosError>): responseInterface<PaginatedResult<ActivityLog>, AxiosError> => {
    const key = useUserSWRContentKey([ 'account', 'activity', page.toString() ]);

    return useSWR<PaginatedResult<ActivityLog>>(key, async () => {
        const { data } = await http.get('/api/client/account/activity', {
            params: {
                include: [ 'actor' ],
                sort: '-timestamp',
                page: page,
            },
        });

        return toPaginatedSet(data, Transformers.toActivityLog);
    }, { revalidateOnMount: false, ...(config || {}) });
};

export { useActivityLogs };
