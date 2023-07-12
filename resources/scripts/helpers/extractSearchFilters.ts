import { QueryBuilderParams } from '@/api/http';
import splitStringWhitespace from '@/helpers/splitStringWhitespace';

interface Options<D extends string = string> {
    defaultFilter?: D;
    splitUnmatched?: boolean;
    returnUnmatched?: boolean;
}

const extractSearchFilters = <T extends string, D extends string = '*'>(
    str: string,
    params: Readonly<T[]>,
    options?: Options<D>,
): QueryBuilderParams<T> | QueryBuilderParams<D> | QueryBuilderParams<T & D> => {
    const opts: Required<Options<D>> = {
        defaultFilter: options?.defaultFilter || ('*' as D),
        splitUnmatched: options?.splitUnmatched || false,
        returnUnmatched: options?.returnUnmatched || false,
    };

    const filters: Map<T, string[]> = new Map();
    const unmatched: string[] = [];

    for (const segment of splitStringWhitespace(str)) {
        const parts = segment.split(':');
        const filter = parts[0] as T;
        const value = parts.slice(1).join(':');
        if (!filter || (parts.length > 1 && filter && !value)) {
            // do nothing
        } else if (!params.includes(filter)) {
            unmatched.push(segment);
        } else {
            filters.set(filter, [...(filters.get(filter) || []), value]);
        }
    }

    if (opts.returnUnmatched && str.trim().length > 0) {
        filters.set(opts.defaultFilter as any, opts.splitUnmatched ? unmatched : [unmatched.join(' ')]);
    }

    if (filters.size === 0) {
        return { filters: {} };
    }

    // @ts-expect-error todo
    return { filters: Object.fromEntries(filters) };
};

export default extractSearchFilters;
