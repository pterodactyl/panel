import tw from 'twin.macro';
import isEqual from 'react-fast-compare';
import useFlash from '@/plugins/useFlash';
import Can from '@/components/elements/Can';
import { ServerContext } from '@/state/server';
import Button from '@/components/elements/Button';
import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import getServerAllocations from '@/api/swr/getServerAllocations';
import AllocationRow from '@/components/server/network/AllocationRow';
import { useDeepCompareEffect } from '@/plugins/useDeepCompareEffect';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import createServerAllocation from '@/api/server/network/createServerAllocation';

const NetworkContainer = () => {
    const [ loading, setLoading ] = useState(false);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const allocationLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.allocations);
    const allocations = ServerContext.useStoreState(state => state.server.data!.allocations, isEqual);
    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = getServerAllocations();

    useEffect(() => {
        mutate(allocations);
    }, []);

    useEffect(() => {
        if (error) {
            clearAndAddHttpError({ key: 'server:network', error });
        }
    }, [ error ]);

    useDeepCompareEffect(() => {
        if (!data) return;

        setServerFromState(state => ({ ...state, allocations: data }));
    }, [ data ]);

    const onCreateAllocation = () => {
        clearFlashes('server:network');

        setLoading(true);
        createServerAllocation(uuid)
            .then(allocation => {
                setServerFromState(s => ({ ...s, allocations: s.allocations.concat(allocation) }));
                return mutate(data?.concat(allocation), false);
            })
            .catch(error => clearAndAddHttpError({ key: 'server:network', error }))
            .then(() => setLoading(false));
    };

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
                            />
                        ))
                    }
                    {allocationLimit > 0 &&
                        <Can action={'allocation.create'}>
                            <SpinnerOverlay visible={loading}/>
                            <div css={tw`mt-6 sm:flex items-center justify-end`}>
                                <p css={tw`text-sm text-neutral-300 mb-4 sm:mr-6 sm:mb-0`}>
                                    You are currently using {data.length} of {allocationLimit} allowed allocations for
                                    this server.
                                </p>
                                {allocationLimit > data.length &&
                                    <Button css={tw`w-full sm:w-auto`} color={'primary'} onClick={onCreateAllocation}>
                                        Create Allocation
                                    </Button>
                                }
                            </div>
                        </Can>
                    }
                </>
            }
        </ServerContentBlock>
    );
};

export default NetworkContainer;
