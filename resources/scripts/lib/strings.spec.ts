import { describe, expect, it } from 'vitest';
import { capitalize } from '@/lib/strings';

describe('@/lib/strings.ts', function () {
    describe('capitalize()', function () {
        it('should capitalize a string', function () {
            expect(capitalize('foo bar')).toBe('Foo bar');
            expect(capitalize('FOOBAR')).toBe('FOOBAR');
        });

        it('should preserve the casing of the remaining characters', function () {
            expect(capitalize('invalid CPU limit.')).toBe('Invalid CPU limit.');
            expect(capitalize('the IPv4 address is invalid')).toBe('The IPv4 address is invalid');
        });

        it('should handle empty strings', function () {
            expect(capitalize('')).toBe('');
        });
    });
});
