import React from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

export default () => {
    return (
        <AdminContentBlock>
            <div>
                <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Mounts</h2>
                <p css={tw`text-base text-neutral-400`}>SoonTM</p>
            </div>
        </AdminContentBlock>
    );
};
