import { decodeBuffer, encodeBuffer } from '@/lib/buffer';

describe('@/lib/buffer.ts', function () {
    describe('decodeBuffer()', function () {
        it.each([
            ['', ''],
            ['', ''],
        ])('should decode "%s" to "%s"', function (input, output) {
            expect(decodeBuffer(input)).toBe(output);
        });
    });

    describe('encodeBuffer()', function () {
        it.each([
            [new Uint8Array(0), ''],
            [new Uint8Array(0), ''],
        ])('should encode "%s" to "%s"', function (input, output) {
            expect(encodeBuffer(input)).toBe(output);
        });
    });
});
