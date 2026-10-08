import type { QueryBuilderParams } from '@/api/queryParameters';
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

export const activityLogQuery = (filters?: ActivityLogFilters): ActivityLogQuery => {
    const event = queryFilterValue(filters?.filters?.event);
    const user = queryFilterValue(filters?.filters?.user);
    const timestamp = filters?.sorts?.timestamp;
    const query: ActivityLogQuery = { include: 'actor' };

    if (filters?.page !== undefined) {
        query.page = filters.page;
    }

    if (event) {
        query['filter[event_name]'] = event;
    }

    if (user !== undefined && Number.isFinite(Number(user))) {
        query['filter[user_id]'] = Number(user);
    }

    if (timestamp === -1 || timestamp === 'desc') {
        query.sort = '-timestamp';
    }

    if (timestamp === 1 || timestamp === 'asc') {
        query.sort = 'timestamp';
    }

    return query;
};
