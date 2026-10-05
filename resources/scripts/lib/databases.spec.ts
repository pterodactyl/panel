import { describe, expect, it } from 'vitest';
import { serverDatabaseShortName } from './databases';

describe('serverDatabaseShortName', () => {
    it('keeps everything after the server prefix', () => {
        expect(serverDatabaseShortName('s3_my_world')).toBe('my_world');
        expect(serverDatabaseShortName('s12_survival')).toBe('survival');
    });

    it('returns a name without a prefix unchanged', () => {
        expect(serverDatabaseShortName('standalone')).toBe('standalone');
    });
});
