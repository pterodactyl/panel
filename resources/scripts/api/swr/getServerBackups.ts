import useSWR from 'swr';
import { ServerContext } from '@/state/server';
import { ServerBackup } from '@/api/server/types';
import { createContext, useContext } from 'react';
import { rawDataToServerBackup } from '@/api/transformers';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

type BackupResponse = PaginatedResult<ServerBackup> & { backupCount: number };

export default () => {
    const { page } = useContext(Context);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<BackupResponse>(['server:backups', uuid, page], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/backups`, { params: { page } });

        return {
            items: (data.data || []).map(rawDataToServerBackup),
            pagination: getPaginationSet(data.meta.pagination),
            backupCount: data.meta.backup_count,
        };
    });
};
