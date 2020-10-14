import React, { useCallback, useEffect } from 'react';
import useSWR from 'swr';
import getServerAllocations from '@/api/server/network/getServerAllocations';
import { Allocation } from '@/api/server/getServer';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext } from '@/state/server';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import AllocationRow from '@/components/server/network/AllocationRow';
import setPrimaryServerAllocation from '@/api/server/network/setPrimaryServerAllocation';

const NetworkContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const allocations = useDeepMemoize(ServerContext.useStoreState(state => state.server.data!.allocations));

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = useSWR<Allocation[]>(uuid, key => getServerAllocations(key), {
        initialData: allocations,
        revalidateOnFocus: false,
    });

    useEffect(() => {
        if (error) {
            clearAndAddHttpError({ key: 'server:network', error });
        }
    }, [ error ]);

    const setPrimaryAllocation = useCallback((id: number) => {
        clearFlashes('server:network');

        const initial = data;
        mutate(data?.map(a => a.id === id ? { ...a, isDefault: true } : { ...a, isDefault: false }), false);

        setPrimaryServerAllocation(uuid, id)
            .catch(error => {
                clearAndAddHttpError({ key: 'server:network', error });
                mutate(initial, false);
            });
    }, []);

    const onNotesAdded = useCallback((id: number, notes: string) => {
        mutate(data?.map(a => a.id === id ? { ...a, notes } : a), false);
    }, []);

    return (
        <ServerContentBlock showFlashKey={'server:network'} title={'Network'}>
            {!data ?
                <Spinner size={'large'} centered/>
                :
                data.map(allocation => (
                    <AllocationRow
                        key={`${allocation.ip}:${allocation.port}`}
                        allocation={allocation}
                        onSetPrimary={setPrimaryAllocation}
                        onNotesChanged={onNotesAdded}
                    />
                ))
            }
        </ServerContentBlock>
    );
};

export default NetworkContainer;
