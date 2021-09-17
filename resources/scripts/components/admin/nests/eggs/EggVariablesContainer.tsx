import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import { Egg } from '@/api/admin/eggs/getEgg';

export default ({ egg }: { egg: Egg }) => {
    return (
        <AdminBox title={'Variables'}>
            {egg.name}
        </AdminBox>
    );
};
