type QueryFilterPrimitive = string | number | boolean | null;

export type QueryFilterValue = QueryFilterPrimitive | undefined | ReadonlyArray<QueryFilterPrimitive>;
export type QuerySortValue = -1 | 0 | 1 | 'asc' | 'desc' | null | undefined;

export interface QueryBuilderParams<FilterKeys extends string = string, SortKeys extends string = string> {
    page?: number;
    filters?: {
        [K in FilterKeys]?: QueryFilterPrimitive | Readonly<QueryFilterPrimitive[]>;
    };
    sorts?: {
        [K in SortKeys]?: Exclude<QuerySortValue, undefined>;
    };
}

export const queryFilterValue = (value: QueryFilterValue) => {
    if (value === undefined || value === null || value === '') {
        return undefined;
    }

    const normalized = Array.isArray(value) ? value.join(',') : String(value);

    return normalized.length > 0 ? normalized : undefined;
};

export const querySortValue = (field: string, value: QuerySortValue) => {
    if (!value || !['asc', 'desc', 1, -1].includes(value)) {
        return undefined;
    }

    return value === -1 || value === 'desc' ? `-${field}` : field;
};

export const querySortList = <TSort extends string>(sorts: Partial<Record<TSort, QuerySortValue>> | undefined) => {
    if (!sorts) {
        return [];
    }

    return (Object.entries(sorts) as [TSort, QuerySortValue][]).flatMap(([field, value]) => {
        const sort = querySortValue(field, value);

        return sort ? [sort] : [];
    });
};

export type ListSort<TField extends string> = TField | `-${TField}`;

export const listSorts = <TField extends string>(
    fields: readonly TField[],
    sort: ListSort<TField> | undefined
): Partial<Record<TField, 'asc' | 'desc'>> | undefined => {
    const descending = sort?.startsWith('-') === true;
    const field = fields.find((candidate) => candidate === (descending ? sort?.slice(1) : sort));
    if (field === undefined) {
        return undefined;
    }

    const sorts: Partial<Record<TField, 'asc' | 'desc'>> = {};
    sorts[field] = descending ? 'desc' : 'asc';

    return sorts;
};

export type ListQuery<TFilter extends string> = Partial<Record<`filter[${TFilter}]`, string>> & {
    page?: number;
    per_page?: number;
    sort?: string;
};

export const listQuery = <TFilter extends string, TSort extends string>(
    params: QueryBuilderParams<TFilter, TSort> = {},
    perPage?: number
): ListQuery<TFilter> => {
    const filters: Partial<Record<`filter[${TFilter}]`, string>> = {};
    for (const [field, value] of Object.entries(params.filters ?? {}) as [TFilter, QueryFilterValue][]) {
        const normalized = queryFilterValue(value);
        if (normalized !== undefined) filters[`filter[${field}]` as const] = normalized;
    }

    const query: ListQuery<TFilter> = { ...filters };
    const sorts = querySortList(params.sorts);
    if (sorts.length > 0) query.sort = sorts.join(',');
    if (params.page !== undefined) query.page = params.page;
    if (perPage !== undefined) query.per_page = perPage;

    return query;
};
