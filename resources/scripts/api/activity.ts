import type { QueryBuilderParams, QuerySortValue } from '@/api/queryParameters';
import { queryFilterValue } from '@/api/queryParameters';
import type { AdminActivityLogResource, ClientActivityLogResource } from '@/api/generated';

export type ActivityLogFilters = QueryBuilderParams<'event' | 'user', 'timestamp'>;

export type ActivityLog = ClientActivityLogResource | AdminActivityLogResource;

type ActivityLogQuery = {
    page?: number;
    'filter[event_name]'?: string;
    'filter[user_id]'?: number;
    sort?: 'timestamp' | '-timestamp';
    include: 'actor';
};

const timestampSorts = new Map<QuerySortValue, ActivityLogQuery['sort']>([
    [-1, '-timestamp'],
    ['desc', '-timestamp'],
    [1, 'timestamp'],
    ['asc', 'timestamp'],
]);

const userIdFilter = (user: string | undefined): number | undefined => {
    if (user === undefined || !Number.isFinite(Number(user))) {
        return undefined;
    }

    return Number(user);
};

export const activityLogQuery = (filters?: ActivityLogFilters): ActivityLogQuery => {
    const event = queryFilterValue(filters?.filters?.event);
    const userId = userIdFilter(queryFilterValue(filters?.filters?.user));
    const sort = timestampSorts.get(filters?.sorts?.timestamp);
    const query: ActivityLogQuery = { include: 'actor' };

    if (filters?.page !== undefined) {
        query.page = filters.page;
    }

    if (event) {
        query['filter[event_name]'] = event;
    }

    if (userId !== undefined) {
        query['filter[user_id]'] = userId;
    }

    if (sort) {
        query.sort = sort;
    }

    return query;
};
