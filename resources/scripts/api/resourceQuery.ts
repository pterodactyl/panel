export interface ResourceQueryOverrides {
    enabled?: boolean;
}

/** `overrides.enabled` can only disable the query. */
export const withQueryOverrides = <TQuery extends { enabled: boolean }, TOverrides extends ResourceQueryOverrides>(
    query: TQuery,
    overrides?: TOverrides
) => ({
    ...query,
    ...overrides,
    enabled: query.enabled && overrides?.enabled !== false,
});
