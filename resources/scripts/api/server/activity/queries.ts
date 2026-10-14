import {
    clientListServerActivityFilterOptionsOptions,
    clientListServerActivityOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import type { ClientListServerActivityData } from '@/api/generated';
import { activityLogQuery, type ActivityLogFilters } from '@/api/activity';

export const serverActivityLogsQueryOptions = (
    uuid: string,
    filters?: ActivityLogFilters
): ReturnType<typeof clientListServerActivityOptions> & { enabled: boolean } => ({
    ...clientListServerActivityOptions({
        path: { server_uuid: uuid },
        query: activityLogQuery(filters) satisfies ClientListServerActivityData['query'],
    }),
    enabled: !!uuid,
});

export const serverActivityFacetsQueryOptions = (
    uuid: string
): ReturnType<typeof clientListServerActivityFilterOptionsOptions> & { enabled: boolean } => ({
    ...clientListServerActivityFilterOptionsOptions({ path: { server_uuid: uuid } }),
    enabled: !!uuid,
});
