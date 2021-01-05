import React from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

export default () => {
    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Create Server</h2>
                    <p css={tw`text-base text-neutral-400`}>Add a new server to the panel.</p>
                </div>
            </div>
        </AdminContentBlock>
    );
};
