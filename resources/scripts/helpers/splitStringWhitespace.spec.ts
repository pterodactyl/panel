import { describe, expect, it } from 'vitest';

import splitStringWhitespace from '@/helpers/splitStringWhitespace';

describe('@/helpers/splitStringWhitespace.ts', function () {
    it.each([
        ['', []],
        ['hello world', ['hello', 'world']],
        ['   hello world ', ['hello', 'world']],
        ['hello123 world 123 $$ s ', ['hello123', 'world', '123', '$$', 's']],
        ['hello world! how are you?', ['hello', 'world!', 'how', 'are', 'you?']],
        ['hello "foo bar baz" world', ['hello', 'foo bar baz', 'world']],
        ['hello "foo \\"bar bar \\" baz" world', ['hello', 'foo "bar bar " baz', 'world']],
        ['hello "foo "bar baz" baz" world', ['hello', 'foo bar', 'baz baz', 'world']],
    ])('should handle string: %s', function (input, output) {
        expect(splitStringWhitespace(input)).toStrictEqual(output);
    });
});
