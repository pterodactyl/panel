import { ServerContext } from '@/state/server';
import useSWR from 'swr';
import http from '@/api/http';
import { rawDataToServerAllocation } from '@/api/transformers';
import { Allocation } from '@/api/server/getServer';

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<Allocation[]>(
        ['server:allocations', uuid],
        async () => {
            const { data } = await http.get(`/api/client/servers/${uuid}/network/allocations`);

            return (data.data || []).map(rawDataToServerAllocation);
        },
        { revalidateOnFocus: false, revalidateOnMount: false }
    );
};
