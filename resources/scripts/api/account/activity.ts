import useUserSWRContentKey from '@/plugins/useUserSWRContentKey';
import useSWR, { ConfigInterface, responseInterface } from 'swr';
import { ActivityLog, Transformers } from '@definitions/user';
import { AxiosError } from 'axios';
import http, { PaginatedResult, QueryBuilderParams, withQueryBuilderParams } from '@/api/http';
import { toPaginatedSet } from '@definitions/helpers';
import useFilteredObject from '@/plugins/useFilteredObject';

export type ActivityLogFilters = QueryBuilderParams<'ip' | 'event', 'timestamp'>;

const useActivityLogs = (
    filters?: ActivityLogFilters,
    config?: ConfigInterface<PaginatedResult<ActivityLog>, AxiosError>
): responseInterface<PaginatedResult<ActivityLog>, AxiosError> => {
    const key = useUserSWRContentKey(['account', 'activity', JSON.stringify(useFilteredObject(filters || {}))]);

    return useSWR<PaginatedResult<ActivityLog>>(
        key,
        async () => {
            const { data } = await http.get('/api/client/account/activity', {
                params: {
                    ...withQueryBuilderParams(filters),
                    include: ['actor'],
                },
            });

            return toPaginatedSet(data, Transformers.toActivityLog);
        },
        { revalidateOnMount: false, ...(config || {}) }
    );
};

export { useActivityLogs };
