import { describe, expect, it } from 'vitest';

import extractSearchFilters from '@/helpers/extractSearchFilters';

type TestCase = [string, 0 | Record<string, string[]>];

describe('@/helpers/extractSearchFilters.ts', function () {
    const cases: TestCase[] = [
        ['', {}],
        ['hello world', {}],
        ['bar:xyz foo:abc', { bar: ['xyz'], foo: ['abc'] }],
        ['hello foo:abc', { foo: ['abc'] }],
        ['hello foo:abc world another bar:xyz hodor', { foo: ['abc'], bar: ['xyz'] }],
        ['foo:1 foo:2 foo: 3 foo:4', { foo: ['1', '2', '4'] }],
        [' foo:123 foo:bar:123 foo: foo:string', { foo: ['123', 'bar:123', 'string'] }],
        ['foo:1 bar:2 baz:3', { foo: ['1'], bar: ['2'] }],
        ['hello "world this" is quoted', {}],
        ['hello "world foo:123 is" quoted', {}],
        [
            'hello foo:"this is quoted" bar:"this \\"is deeply\\" quoted" world foo:another',
            {
                foo: ['this is quoted', 'another'],
                bar: ['this "is deeply" quoted'],
            },
        ],
    ];

    it.each(cases)('should return expected filters: [%s]', function (input, output) {
        expect(extractSearchFilters(input, ['foo', 'bar'])).toStrictEqual({
            filters: output,
        });
    });

    it('should allow modification of the default parameter', function () {
        expect(
            extractSearchFilters('hello world', ['foo'], { defaultFilter: 'default_param', returnUnmatched: true }),
        ).toStrictEqual({
            filters: {
                default_param: ['hello world'],
            },
        });

        expect(extractSearchFilters('foo:123 bar', ['foo'], { defaultFilter: 'default_param' })).toStrictEqual({
            filters: {
                foo: ['123'],
            },
        });
    });

    it.each([
        ['', {}],
        ['hello world', { '*': ['hello world'] }],
        ['hello world foo:123 bar:456', { foo: ['123'], bar: ['456'], '*': ['hello world'] }],
        ['hello world foo:123 another string', { foo: ['123'], '*': ['hello world another string'] }],
    ])('should return unmatched parameters: %s', function (input, output) {
        expect(extractSearchFilters(input, ['foo', 'bar'], { returnUnmatched: true })).toStrictEqual({
            filters: output,
        });
    });

    it.each([
        ['', {}],
        ['hello world', { '*': ['hello', 'world'] }],
        ['hello world foo:123 bar:456', { foo: ['123'], bar: ['456'], '*': ['hello', 'world'] }],
        ['hello world foo:123 another string', { foo: ['123'], '*': ['hello', 'world', 'another', 'string'] }],
    ])('should split unmatched parameters: %s', function (input, output) {
        expect(
            extractSearchFilters(input, ['foo', 'bar'], {
                returnUnmatched: true,
                splitUnmatched: true,
            }),
        ).toStrictEqual({
            filters: output,
        });
    });

    it.each([true, false])('should return the unsplit value (splitting: %s)', function (split) {
        const extracted = extractSearchFilters('hello foo:123 bar:123 world', ['foo'], {
            returnUnmatched: true,
            splitUnmatched: split,
        });
        expect(extracted).toStrictEqual({
            filters: {
                foo: ['123'],
                '*': split ? ['hello', 'bar:123', 'world'] : ['hello bar:123 world'],
            },
        });
    });
});
