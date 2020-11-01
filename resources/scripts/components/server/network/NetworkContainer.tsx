import React, { useCallback, useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext } from '@/state/server';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import AllocationRow from '@/components/server/network/AllocationRow';
import setPrimaryServerAllocation from '@/api/server/network/setPrimaryServerAllocation';
import Button from '@/components/elements/Button';
import createServerAllocation from '@/api/server/network/createServerAllocation';
import tw from 'twin.macro';
import Can from '@/components/elements/Can';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import getServerAllocations from '@/api/swr/getServerAllocations';

const NetworkContainer = () => {
    const [ loading, setLoading ] = useState(false);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const allocationLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.allocations);
    const allocations = useDeepMemoize(ServerContext.useStoreState(state => state.server.data!.allocations));

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = getServerAllocations(allocations);

    useEffect(() => {
        if (error) {
            clearAndAddHttpError({ key: 'server:network', error });
        }
    }, [ error ]);

    const setPrimaryAllocation = (id: number) => {
        clearFlashes('server:network');

        const initial = data;
        mutate(data?.map(a => a.id === id ? { ...a, isDefault: true } : { ...a, isDefault: false }), false);

        setPrimaryServerAllocation(uuid, id)
            .catch(error => {
                clearAndAddHttpError({ key: 'server:network', error });
                mutate(initial, false);
            });
    };

    const onCreateAllocation = () => {
        clearFlashes('server:network');

        setLoading(true);
        createServerAllocation(uuid)
            .then(allocation => mutate(data?.concat(allocation), false))
            .catch(error => clearAndAddHttpError({ key: 'server:network', error }))
            .then(() => setLoading(false));
    };

    const onNotesAdded = useCallback((id: number, notes: string) => {
        mutate(data?.map(a => a.id === id ? { ...a, notes } : a), false);
    }, []);

    return (
        <ServerContentBlock showFlashKey={'server:network'} title={'Network'}>
            {!data ?
                <Spinner size={'large'} centered/>
                :
                <>
                    {
                        data.map(allocation => (
                            <AllocationRow
                                key={`${allocation.ip}:${allocation.port}`}
                                allocation={allocation}
                                onSetPrimary={setPrimaryAllocation}
                                onNotesChanged={onNotesAdded}
                            />
                        ))
                    }
                    <Can action={'allocation.create'}>
                        <SpinnerOverlay visible={loading}/>
                        <div css={tw`mt-6 sm:flex items-center justify-end`}>
                            <p css={tw`text-sm text-neutral-300 mb-4 sm:mr-6 sm:mb-0`}>
                                You are currently using {data.length} of {allocationLimit} allowed allocations for this
                                server.
                            </p>
                            {allocationLimit > data.length &&
                            <Button css={tw`w-full sm:w-auto`} color={'primary'} onClick={onCreateAllocation}>
                                Create Allocation
                            </Button>
                            }
                        </div>
                    </Can>
                </>
            }
        </ServerContentBlock>
    );
};

export default NetworkContainer;
