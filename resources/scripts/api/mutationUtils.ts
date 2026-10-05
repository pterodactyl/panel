import type { Query, QueryClient, QueryFilters } from '@tanstack/react-query';
import { hasGeneratedOperationId } from '@/api/queryKeyGuards';

export type ResourceMutationAction = 'create' | 'update' | 'delete';

const resourceMutationCopy = {
    create: { verb: 'create', pastTense: 'created' },
    update: { verb: 'update', pastTense: 'updated' },
    delete: { verb: 'delete', pastTense: 'deleted' },
} satisfies Record<ResourceMutationAction, { verb: string; pastTense: string }>;

const sentenceCase = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);

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

export const resourceMutationMessages = (resource: string, action: ResourceMutationAction, name?: string) => {
    const copy = resourceMutationCopy[action];

    return {
        errorTitle: `Unable to ${copy.verb} ${resource}`,
        success: {
            title: `${sentenceCase(resource)} ${copy.pastTense}`,
            description: name ? `${name} has been ${copy.pastTense}.` : `The ${resource} has been ${copy.pastTense}.`,
        },
    };
};
