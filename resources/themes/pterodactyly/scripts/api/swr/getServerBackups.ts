import useSWR from 'swr';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { ServerBackup } from '@/api/server/types';
import { rawDataToServerBackup } from '@/api/transformers';
import { ServerContext } from '@/state/server';

export default (page?: number | string) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    return useSWR<PaginatedResult<ServerBackup>>([ 'server:backups', uuid, page ], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/backups`, { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToServerBackup),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
