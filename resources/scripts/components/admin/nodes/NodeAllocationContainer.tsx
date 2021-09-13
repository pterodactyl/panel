import AllocationTable from '@/components/admin/nodes/allocations/AllocationTable';
import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import React from 'react';
import { useRouteMatch } from 'react-router-dom';
import AdminBox from '@/components/admin/AdminBox';
import CreateAllocationForm from '@/components/admin/nodes/allocations/CreateAllocationForm';
import tw from 'twin.macro';

export default () => {
    const match = useRouteMatch<{ id: string }>();

    return (
        <>
            <div css={tw`w-full grid grid-cols-12 gap-x-8`}>
                <div css={tw`w-full flex col-span-8`}>
                    <AllocationTable nodeId={match.params.id}/>
                </div>

                <div css={tw`w-full flex col-span-4`}>
                    <AdminBox icon={faNetworkWired} title={'Allocations'} css={tw`h-auto w-full`}>
                        <CreateAllocationForm nodeId={match.params.id}/>
                    </AdminBox>
                </div>
            </div>
        </>
    );
};
