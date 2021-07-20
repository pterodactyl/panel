import React from 'react';
import { useRouteMatch } from 'react-router-dom';
import AdminBox from '@/components/admin/AdminBox';
import CreateAllocationForm from '@/components/admin/nodes/CreateAllocationForm';

export default () => {
    const match = useRouteMatch<{ id: string }>();

    return (
        <AdminBox title={'Allocations'}>
            <CreateAllocationForm nodeId={match.params.id}/>
        </AdminBox>
    );
};
