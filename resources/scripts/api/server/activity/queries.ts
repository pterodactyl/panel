import { useQuery } from '@tanstack/react-query';
import {
    clientListServerActivityFilterOptionsOptions,
    clientListServerActivityOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import type { ClientListServerActivityData } from '@/api/generated';
import { useCurrentServerUuid } from '@/api/server/queries';
import { activityLogQuery, type ActivityLogFilters } from '@/api/activity';
import { emptyActivityFacets, toActivityFacets } from '@/api/activityFacets';

export const serverActivityLogsQueryOptions = (uuid: string, filters?: ActivityLogFilters) => ({
    ...clientListServerActivityOptions({
        path: { server_uuid: uuid },
        query: activityLogQuery(filters) satisfies ClientListServerActivityData['query'],
    }),
    enabled: !!uuid,
});

const useActivityLogs = (
    filters?: ActivityLogFilters,
    config?: Pick<
        ReturnType<typeof clientListServerActivityOptions>,
        'enabled' | 'refetchOnWindowFocus' | 'placeholderData'
    >
) => {
    const uuid = useCurrentServerUuid() ?? '';

    return useQuery({
        ...serverActivityLogsQueryOptions(uuid, filters),
        ...config,
        enabled: !!uuid && config?.enabled !== false,
    });
};

export const serverActivityFacetsQueryOptions = (uuid: string) => ({
    ...clientListServerActivityFilterOptionsOptions({ path: { server_uuid: uuid } }),
    enabled: !!uuid,
});

const useActivityFacets = () => {
    const uuid = useCurrentServerUuid() ?? '';

    return (
        useQuery({ ...serverActivityFacetsQueryOptions(uuid), select: toActivityFacets }).data ?? emptyActivityFacets
    );
};

export { useActivityFacets, useActivityLogs };
