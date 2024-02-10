import { describe, expect, it } from 'vitest';

import { capitalize } from '@/lib/strings';

describe('@/lib/strings.ts', () => {
    describe('capitalize()', () => {
        it('should capitalize a string', () => {
            expect(capitalize('foo bar')).equals('Foo bar');
            expect(capitalize('FOOBAR')).equals('Foobar');
        });

        it('should handle empty strings', () => {
            expect(capitalize('')).equals('');
        });
    });
});
