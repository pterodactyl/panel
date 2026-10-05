import { useSuspenseQuery } from '@tanstack/react-query';
import { getRouteApi } from '@tanstack/react-router';
import { type AdminServer, adminServerQueryOptions, useInvalidateAdminServer } from '@/api/admin/servers/queries';

const serverDetailRoute = getRouteApi('/authenticated/panel/servers/$id');

interface ServerDetail {
    server: AdminServer;
    reload: () => void;
}

export const useServerDetail = (): ServerDetail => {
    const { id } = serverDetailRoute.useParams();
    const reload = useInvalidateAdminServer(id);
    const { data: server } = useSuspenseQuery(adminServerQueryOptions(id));

    return { server, reload };
};
