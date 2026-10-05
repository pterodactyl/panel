import { useQuery } from '@tanstack/react-query';
import {
    adminListActivityFilterOptionsOptions,
    adminListActivityOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import type { AdminListActivityData } from '@/api/generated';
import { activityLogQuery, type ActivityLogFilters } from '@/api/activity';
import { emptyActivityFacets, toActivityFacets } from '@/api/activityFacets';

export const adminActivityLogsQueryOptions = (filters?: ActivityLogFilters) =>
    adminListActivityOptions({ query: activityLogQuery(filters) satisfies AdminListActivityData['query'] });

export const useAdminActivityLogs = (
    filters?: ActivityLogFilters,
    config?: Pick<ReturnType<typeof adminListActivityOptions>, 'enabled' | 'refetchOnWindowFocus' | 'placeholderData'>
) => useQuery({ ...adminActivityLogsQueryOptions(filters), ...config });

export const adminActivityFacetsQueryOptions = () => adminListActivityFilterOptionsOptions();

export const useAdminActivityFacets = () =>
    useQuery({ ...adminActivityFacetsQueryOptions(), select: toActivityFacets }).data ?? emptyActivityFacets;
