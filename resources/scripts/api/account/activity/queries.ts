import { useQuery } from '@tanstack/react-query';
import {
    clientListAccountActivityFilterOptionsOptions,
    clientListAccountActivityOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import type { ClientListAccountActivityData } from '@/api/generated';
import { activityLogQuery, type ActivityLogFilters } from '@/api/activity';
import { emptyActivityFacets, toActivityFacets } from '@/api/activityFacets';

export const accountActivityLogsQueryOptions = (filters?: ActivityLogFilters) => ({
    ...clientListAccountActivityOptions({
        query: activityLogQuery(filters) satisfies ClientListAccountActivityData['query'],
    }),
});

type ActivityLogsQueryOptions = Pick<
    ReturnType<typeof clientListAccountActivityOptions>,
    'enabled' | 'refetchOnWindowFocus' | 'placeholderData'
>;

const useActivityLogs = (filters?: ActivityLogFilters, config?: Partial<ActivityLogsQueryOptions>) =>
    useQuery({ ...accountActivityLogsQueryOptions(filters), ...config });

export const accountActivityFacetsQueryOptions = () => clientListAccountActivityFilterOptionsOptions();

const useActivityFacets = () =>
    useQuery({ ...accountActivityFacetsQueryOptions(), select: toActivityFacets }).data ?? emptyActivityFacets;

export { useActivityFacets, useActivityLogs };
