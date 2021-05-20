import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import { Context } from '@/components/admin/nests/eggs/EggRouter';

export default () => {
    const egg = Context.useStoreState(state => state.egg);

    if (egg === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Egg Information'}>

        </AdminBox>
    );
};
