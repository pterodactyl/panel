import useSWR from 'swr';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { ServerBackup } from '@/api/server/types';
import { rawDataToServerBackup } from '@/api/server/transformers';
import useServer from '@/plugins/useServer';

export default (page?: number | string) => {
    const { uuid } = useServer();

    return useSWR<PaginatedResult<ServerBackup>>([ 'server:backups', uuid, page ], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/backups`, { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToServerBackup),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
