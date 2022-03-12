import extractSearchFilters from '@/helpers/extractSearchFilters';

type TestCase = [ string, 0 | Record<string, string[]> ];

describe('@/helpers/extractSearchFilters.ts', function () {
    const _DEFAULT = 0x00;
    const cases: TestCase[] = [
        [ '', {} ],
        [ 'hello world', _DEFAULT ],
        [ 'bar:xyz foo:abc', { bar: [ 'xyz' ], foo: [ 'abc' ] } ],
        [ 'hello foo:abc', { foo: [ 'abc' ] } ],
        [ 'hello foo:abc world another bar:xyz hodor', { foo: [ 'abc' ], bar: [ 'xyz' ] } ],
        [ 'foo:1 foo:2 foo: 3 foo:4', { foo: [ '1', '2', '4' ] } ],
        [ ' foo:123 foo:bar:123 foo: foo:string', { foo: [ '123', 'bar:123', 'string' ] } ],
        [ 'foo:1 bar:2 baz:3', { foo: [ '1' ], bar: [ '2' ] } ],
        [ 'hello "world this" is quoted', _DEFAULT ],
        [ 'hello "world foo:123 is" quoted', _DEFAULT ],
        [ 'hello foo:"this is quoted" bar:"this \\"is deeply\\" quoted" world foo:another', {
            foo: [ 'this is quoted', 'another' ],
            bar: [ 'this "is deeply" quoted' ],
        } ],
    ];

    it.each(cases)('should return expected filters: [%s]', function (input, output) {
        expect(extractSearchFilters(input, [ 'foo', 'bar' ])).toStrictEqual({
            filters: output === _DEFAULT ? {
                '*': [ input ],
            } : output,
        });
    });

    it('should allow modification of the default parameter', function () {
        expect(extractSearchFilters('hello world', [ 'foo' ], 'default_param')).toStrictEqual({
            filters: {
                default_param: [ 'hello world' ],
            },
        });

        expect(extractSearchFilters('foo:123 bar', [ 'foo' ], 'default_param')).toStrictEqual({
            filters: {
                foo: [ '123' ],
            },
        });
    });
});
