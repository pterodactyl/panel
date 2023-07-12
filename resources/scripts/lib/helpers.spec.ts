import { describe, expect, it } from 'vitest';

import { hexToRgba } from '@/lib/helpers';

describe('@/lib/helpers.ts', () => {
    describe('hexToRgba()', () => {
        it('should return the expected rgba', () => {
            expect(hexToRgba('#ffffff')).equals('rgba(255, 255, 255, 1)');
            expect(hexToRgba('#00aabb')).equals('rgba(0, 170, 187, 1)');
            expect(hexToRgba('#efefef')).equals('rgba(239, 239, 239, 1)');
        });

        it('should ignore case', () => {
            expect(hexToRgba('#FF00A3')).equals('rgba(255, 0, 163, 1)');
        });

        it('should allow alpha channel changes', () => {
            expect(hexToRgba('#ece5a8', 0.5)).equals('rgba(236, 229, 168, 0.5)');
            expect(hexToRgba('#ece5a8', 0.1)).equals('rgba(236, 229, 168, 0.1)');
            expect(hexToRgba('#000000', 0)).equals('rgba(0, 0, 0, 0)');
        });

        it('should handle invalid strings', () => {
            expect(hexToRgba('')).equals('');
            expect(hexToRgba('foobar')).equals('foobar');
            expect(hexToRgba('#fff')).equals('#fff');
            expect(hexToRgba('#')).equals('#');
            expect(hexToRgba('#fffffy')).equals('#fffffy');
        });
    });
});
