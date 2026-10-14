import {
    clientListAccountActivityFilterOptionsOptions,
    clientListAccountActivityOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import type { ClientListAccountActivityData } from '@/api/generated';
import { activityLogQuery, type ActivityLogFilters } from '@/api/activity';

export const accountActivityLogsQueryOptions = (filters?: ActivityLogFilters) =>
    clientListAccountActivityOptions({
        query: activityLogQuery(filters) satisfies ClientListAccountActivityData['query'],
    });

export const accountActivityFacetsQueryOptions = () => clientListAccountActivityFilterOptionsOptions();
