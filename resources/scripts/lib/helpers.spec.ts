import { hexToRgba } from '@/lib/helpers';

describe('@/lib/helpers.ts', function () {
    describe('hexToRgba()', function () {
        it('should return the expected rgba', function () {
            expect(hexToRgba('#ffffff')).toBe('rgba(255, 255, 255, 1)');
            expect(hexToRgba('#00aabb')).toBe('rgba(0, 170, 187, 1)');
            expect(hexToRgba('#efefef')).toBe('rgba(239, 239, 239, 1)');
        });

        it('should ignore case', function () {
            expect(hexToRgba('#FF00A3')).toBe('rgba(255, 0, 163, 1)');
        });

        it('should allow alpha channel changes', function () {
            expect(hexToRgba('#ece5a8', 0.5)).toBe('rgba(236, 229, 168, 0.5)');
            expect(hexToRgba('#ece5a8', 0.1)).toBe('rgba(236, 229, 168, 0.1)');
            expect(hexToRgba('#000000', 0)).toBe('rgba(0, 0, 0, 0)');
        });

        it('should handle invalid strings', function () {
            expect(hexToRgba('')).toBe('');
            expect(hexToRgba('foobar')).toBe('foobar');
            expect(hexToRgba('#fff')).toBe('#fff');
            expect(hexToRgba('#')).toBe('#');
            expect(hexToRgba('#fffffy')).toBe('#fffffy');
        });
    });
});
