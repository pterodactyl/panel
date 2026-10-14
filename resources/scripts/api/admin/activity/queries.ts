import {
    adminListActivityFilterOptionsOptions,
    adminListActivityOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import type { AdminListActivityData } from '@/api/generated';
import { activityLogQuery, type ActivityLogFilters } from '@/api/activity';

export const adminActivityLogsQueryOptions = (filters?: ActivityLogFilters) =>
    adminListActivityOptions({ query: activityLogQuery(filters) satisfies AdminListActivityData['query'] });

export const adminActivityFacetsQueryOptions = () => adminListActivityFilterOptionsOptions();
