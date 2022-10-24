import { decodeBase64 } from '@/lib/base64';

describe('@/lib/base64.ts', function () {
    describe('decodeBase64()', function () {
        it.each([
            ['', ''],
            ['', ''],
        ])('should decode "%s" to "%s"', function (input, output) {
            expect(decodeBase64(input)).toBe(output);
        });
    });
});
