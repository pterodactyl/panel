import type { Query, QueryClient, QueryFilters } from '@tanstack/react-query';
import { hasGeneratedOperationId } from '@/api/queryKeyGuards';

export const invalidateGeneratedOperations = (
    queryClient: QueryClient,
    operationIds: readonly string[]
): Promise<void> => {
    if (operationIds.length === 0) {
        return Promise.resolve();
    }

    return queryClient.invalidateQueries({
        predicate: ({ queryKey }) => operationIds.some((operationId) => hasGeneratedOperationId(queryKey, operationId)),
    });
};

export const removeQueriesWhenUnobserved = (queryClient: QueryClient, filters: QueryFilters): void => {
    const cache = queryClient.getQueryCache();
    const observed = new Set<Query>();

    for (const query of cache.findAll(filters)) {
        if (query.getObserversCount() === 0) {
            cache.remove(query);
        } else {
            observed.add(query);
        }
    }

    if (observed.size === 0) {
        return;
    }

    const unsubscribe = cache.subscribe((event) => {
        if (!observed.has(event.query)) {
            return;
        }

        if (event.type === 'observerRemoved' && event.query.getObserversCount() === 0) {
            cache.remove(event.query);
        } else if (event.type === 'removed') {
            observed.delete(event.query);
            if (observed.size === 0) {
                unsubscribe();
            }
        }
    });
};
