import type { AdminListActivityFilterOptionsResponseBody } from '@/api/generated';
import { isNumber, isString } from '@/lib/objects';

export interface ActivityFacetUser {
    id: number;
    username: string;
    email: string;
}

export interface ActivityFacets {
    events: string[];
    users: ActivityFacetUser[];
}

export const toActivityFacets = (body: AdminListActivityFilterOptionsResponseBody | undefined): ActivityFacets => ({
    events: (body?.data?.events ?? []).filter(isString),
    users: (body?.data?.users ?? []).flatMap((user) =>
        isNumber(user.id) && isString(user.username)
            ? [{ id: user.id, username: user.username, email: user.email ?? '' }]
            : []
    ),
});

export const emptyActivityFacets: ActivityFacets = toActivityFacets(undefined);
