import { describe, expect, it } from 'vitest';
import { activityLogQuery } from '@/api/activity';

describe('activityLogQuery', () => {
    it('always includes the actor', () => {
        expect(activityLogQuery()).toEqual({ include: 'actor' });
        expect(activityLogQuery({})).toEqual({ include: 'actor' });
    });

    it('passes the page through', () => {
        expect(activityLogQuery({ page: 3 })).toEqual({ include: 'actor', page: 3 });
        expect(activityLogQuery({ page: 0 })).toEqual({ include: 'actor', page: 0 });
    });

    it('filters by event name', () => {
        expect(activityLogQuery({ filters: { event: 'server:power.start' } })).toEqual({
            include: 'actor',
            'filter[event_name]': 'server:power.start',
        });
        expect(activityLogQuery({ filters: { event: ['auth:fail', 'auth:success'] } })).toEqual({
            include: 'actor',
            'filter[event_name]': 'auth:fail,auth:success',
        });
    });

    it.each([null, ''])('omits an empty event filter (%j)', (event) => {
        expect(activityLogQuery({ filters: { event } })).toEqual({ include: 'actor' });
    });

    it('converts a numeric user filter to a number', () => {
        expect(activityLogQuery({ filters: { user: '12' } })).toEqual({ include: 'actor', 'filter[user_id]': 12 });
        expect(activityLogQuery({ filters: { user: 7 } })).toEqual({ include: 'actor', 'filter[user_id]': 7 });
    });

    it.each(['abc', '12abc', 'Infinity', '', null])('drops a non-numeric user filter (%j)', (user) => {
        expect(activityLogQuery({ filters: { user } })).toEqual({ include: 'actor' });
    });

    it.each([
        [-1, '-timestamp'],
        ['desc', '-timestamp'],
        [1, 'timestamp'],
        ['asc', 'timestamp'],
    ] as const)('maps sort %j to %s', (timestamp, sort) => {
        expect(activityLogQuery({ sorts: { timestamp } })).toEqual({ include: 'actor', sort });
    });

    it.each([0, null] as const)('omits an unknown sort (%j)', (timestamp) => {
        expect(activityLogQuery({ sorts: { timestamp } })).toEqual({ include: 'actor' });
    });

    it('combines every parameter', () => {
        expect(
            activityLogQuery({ page: 2, filters: { event: 'user:create', user: '5' }, sorts: { timestamp: 'asc' } })
        ).toEqual({
            include: 'actor',
            page: 2,
            'filter[event_name]': 'user:create',
            'filter[user_id]': 5,
            sort: 'timestamp',
        });
    });
});
