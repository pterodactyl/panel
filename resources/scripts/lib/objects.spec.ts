import { describe, expect, it } from 'vitest';
import { isObject } from '@/lib/objects';

describe('@/lib/objects.ts', () => {
    describe('isObject()', () => {
        it('should return true for objects', () => {
            expect(isObject({})).toBe(true);
            expect(isObject({ foo: 123 })).toBe(true);
            expect(isObject(Object.freeze({}))).toBe(true);
        });

        it('should return false for null', () => {
            expect(isObject(null)).toBe(false);
        });

        it.each([undefined, 123, 'foobar', () => ({}), Function, String(123), isObject, () => null, [], [1, 2, 3]])(
            'should return false for %p',
            (value) => {
                expect(isObject(value)).toBe(false);
            }
        );
    });
});
