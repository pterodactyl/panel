import type { QueryClient } from '@tanstack/react-query';
import { invalidateGeneratedOperations } from '@/api/mutationUtils';
import { adminGetNodeQueryKey, adminListNodeAllocationsQueryKey } from '@/api/generated/@tanstack/react-query.gen';

/** Path-only keys match the cached reads under any query parameters. */
export const adminNodeDetailKey = (nodeId: number) => adminGetNodeQueryKey({ path: { node_id: nodeId } });

export const adminNodeAllocationsKey = (nodeId: number) =>
    adminListNodeAllocationsQueryKey({ path: { node_id: nodeId } });

/** Omitting `nodeIds` invalidates every node. */
export const invalidateAdminNodeAllocations = async (
    queryClient: QueryClient,
    nodeIds?: readonly number[]
): Promise<void> => {
    if (nodeIds === undefined) {
        await invalidateGeneratedOperations(queryClient, [
            'adminListNodes',
            'adminGetNode',
            'adminListNodeAllocations',
        ]);
        return;
    }

    await Promise.all([
        invalidateGeneratedOperations(queryClient, ['adminListNodes']),
        ...[...new Set(nodeIds)].flatMap((nodeId) => [
            queryClient.invalidateQueries({ queryKey: adminNodeDetailKey(nodeId) }),
            queryClient.invalidateQueries({ queryKey: adminNodeAllocationsKey(nodeId) }),
        ]),
    ]);
};
