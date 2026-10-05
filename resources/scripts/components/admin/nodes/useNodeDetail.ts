import { useSuspenseQuery } from '@tanstack/react-query';
import { getRouteApi } from '@tanstack/react-router';
import { adminNodeQueryOptions, useInvalidateAdminNode } from '@/api/admin/nodes/queries';

const nodeDetailRoute = getRouteApi('/authenticated/panel/nodes/$id');

export const useNodeDetail = () => {
    const { id } = nodeDetailRoute.useParams();
    const refresh = useInvalidateAdminNode(id);
    const { data: node } = useSuspenseQuery(adminNodeQueryOptions(id));

    return { node, refresh };
};
