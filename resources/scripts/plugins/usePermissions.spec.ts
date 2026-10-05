import { describe, expect, it } from 'vitest';
import { hasPermission } from './usePermissions';

describe('hasPermission', () => {
    it('grants everything to a wildcard holder', () => {
        expect(hasPermission(['*'], 'ext.votes.view')).toBe(true);
    });

    it('matches group wildcards only within that group', () => {
        expect(hasPermission(['file.read'], 'file.*')).toBe(true);
        expect(hasPermission(['ext.votes.view'], 'ext.votes.*')).toBe(true);
        expect(hasPermission(['ext.polls.view'], 'ext.votes.*')).toBe(false);
        expect(hasPermission(['control.console'], 'file.*')).toBe(false);
    });

    it('requires an exact grant otherwise', () => {
        expect(hasPermission(['ext.votes.view'], 'ext.votes.view')).toBe(true);
        expect(hasPermission(['ext.votes.view'], 'ext.votes.reset')).toBe(false);
    });
});
