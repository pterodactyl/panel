import { QueryBuilderParams } from '@/api/http';
import splitStringWhitespace from '@/helpers/splitStringWhitespace';

const extractSearchFilters = <T extends string, D extends string = string> (
    str: string,
    params: T[],
    defaultFilter: D = '*' as D,
): QueryBuilderParams<T> | QueryBuilderParams<D> => {
    const filters: Map<T, string[]> = new Map();

    if (str.trim().length === 0) {
        return { filters: {} };
    }

    for (const segment of splitStringWhitespace(str)) {
        const parts = segment.split(':');
        const filter = parts[0] as T;
        const value = parts.slice(1).join(':');
        // @ts-ignore
        if (!filter || !value || !params.includes(filter)) {
            continue;
        }

        filters.set(filter, [ ...(filters.get(filter) || []), value ]);
    }

    if (filters.size === 0) {
        return {
            filters: {
                [defaultFilter]: [ str ] as Readonly<string[]>,
            } as unknown as QueryBuilderParams<D>['filters'],
        };
    }

    return {
        filters: Object.fromEntries(filters) as unknown as QueryBuilderParams<T>['filters'],
    };
};

export default extractSearchFilters;
