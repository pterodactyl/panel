import React, { useCallback, useEffect, useState } from 'react';
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
import Button from '@/components/elements/Button';
import newServerAllocation from '@/api/server/network/newServerAllocation';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';

const NetworkContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const allocationLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.allocations);
    const allocations = useDeepMemoize(ServerContext.useStoreState(state => state.server.data!.allocations));
    const [ addingAllocation, setAddingAllocation ] = useState(false);

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

    const getNewAllocation = () => {
        clearFlashes('server:network');
        setAddingAllocation(true);

        const initial = data;

        newServerAllocation(uuid)
            .then(allocation => {
                mutate(data?.concat(allocation), false);
                setAddingAllocation(false);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'server:network', error });
                mutate(initial, false);
                setAddingAllocation(false);
            });
    };

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
            {allocationLimit > data!.length ?
                <GreyRowBox
                    $hoverable={false}
                    css={tw`mt-2 overflow-x-auto flex items-center justify-center`}
                >
                    {addingAllocation ?
                        <Spinner size={'base'} centered/>
                        :
                        <Button
                            color={'primary'}
                            isSecondary
                            onClick={() => getNewAllocation()}
                            css={tw`my-2`}
                        >
                            Add New Allocation
                        </Button>
                    }
                </GreyRowBox>
                :
                <p css={tw`mt-2 text-center text-sm text-neutral-400`}>
                    You have reached the max number of allocations allowed for your server.
                </p>
            }
        </ServerContentBlock>
    );
};

export default NetworkContainer;
