import { describe, expect, it } from 'vitest';
import { capitalize } from '@/lib/strings';

describe('@/lib/strings.ts', () => {
    describe('capitalize()', () => {
        it('should capitalize a string', () => {
            expect(capitalize('foo bar')).toBe('Foo bar');
            expect(capitalize('FOOBAR')).toBe('FOOBAR');
        });

        it('should preserve the casing of the remaining characters', () => {
            expect(capitalize('invalid CPU limit.')).toBe('Invalid CPU limit.');
            expect(capitalize('the IPv4 address is invalid')).toBe('The IPv4 address is invalid');
        });

        it('should handle empty strings', () => {
            expect(capitalize('')).toBe('');
        });
    });
});
